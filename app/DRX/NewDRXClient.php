<?php
namespace App\DRX;

use Barryvdh\Debugbar\Facades\Debugbar;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Request;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\IProcessor;
use function LaravelLang\Locales\Enums\collection;


class NewPostProcessor implements IProcessor
{
    public function processSelect(Builder $query, $response)
    {
        if (!is_array($response)) return $response;
        if (!isset($response[0]['Id'])) return [];
        $result = [];
        foreach ($response as $item) {
            $entity = $item["properties"];
            if (isset($entity["@odata.type"])) {
                $chuncks = explode(".", $entity["@odata.type"]);
                $entity["@odata.type"] = end($chuncks);
            }
            $result[] = $entity;
        }
        return $result;
    }
}


//TODO: Сделать обработку ошибок при работе с сервисом интеграции

///
/// Класс для получения данных из Сервиса Интеграции Directum RX
class NewDRXClient
{
    protected $type = '';
    protected $odata;
    protected $filter = []; // хранится фильтр в том виде, как он используется в ODataClient
    protected $orders = []; // хранится сортировка в том виде, как он используется в ODataClient
    protected $expand = [];
    protected $pagination = [];

    public function __construct(string $EntityType)
    {
        $url = config('srq.url');
        $login = Auth()->user()->DrxAccount->DRX_Login;
        $password = Auth()->user()->DrxAccount->DRX_Password;
        $httpProvider = new GuzzleHttpProvider();
        $httpProvider->setExtraOptions(['defaults' => [
            'verify' => 'false'
        ]]);
        $this->odata = new ODataClient($url,
            function ($request) use ($login, $password) {
                $request->headers['Authorization'] = 'Basic ' . base64_encode($login . ':' . $password);
            },
            $httpProvider
        );
        $this->odata->setPostProcessor(new NewPostProcessor());
        $this->type = $EntityType;
    }

    public function where($filter)
    {
        if (!is_array($filter)) $filter = [$filter];
        $this->filter = array_merge($this->filter, $filter);
        return $this;
    }

    public function order($properties = [])
    {
        $order = is_array($properties) && count(func_get_args()) === 1 ? $properties : func_get_args();
        if (!(isset($order[0]) && is_array($order[0]))) {
            $order = array($order);
        }
        $this->orders = $order;
        return $this;
    }

    public function with(string|array $fields)
    {
        if (!is_array($fields)) $fields = [$fields];
        $this->expand = array_merge($this->expand, $fields);
        return $this;
    }


    public function find(int $Id)
    {
        $query = $this->odata->from($this->EntityType);
        if ($this->expand)
            $query = $this->$query->expand($this->expand);
        $entity = $query->find($Id);
        return $entity;
    }

    public function saveEntity($EntityType, $Entity, $ExpandFields = [], $CollectionFields = [])
    {
//        dd($CollectionFields);
        $Id = isset($Entity['Id']) ? (int)$Entity['Id'] : null;
        unset($Entity['Id']);
        unset($Entity['Renter']);
        foreach ($Entity as $key => $field) {
            if (is_array($field) && isset($field['Id'])) {
                $Entity[$key]['Id'] = (int)$field['Id'];
            }
        }
        // Обрабатываем поля-коллекции из списка $this->CollectionFields
        foreach ($CollectionFields as $cf) {
            if (isset($Entity[$cf])) {
                // ..исправлям баг|фичу, из-за которой поле Matrix начинает нумерацию строк с единицы
                $Entity[$cf] = array_values($Entity[$cf]);
            }
            // ..потом очищаем поле на сервере DRX, чтоб заполнить его новыми значениями
            if ($Id && isset($Entity[$cf]))
                $this->delete("{$EntityType}({$Id})/$cf");
        }
        if ($Id) {            // Обновляем запись
            $Entity = ($this->from($EntityType)->expand($ExpandFields)->whereKey($Id)->patch($Entity))[0];
        } else {            // Создаём запись
            $Entity = ($this->from($EntityType)->expand($ExpandFields)->post($Entity))[0];
        }
        return $Entity;
    }

    // получает сущности из DRX с учётом типа, дополнительных полей, фильтров и сортировки
    public function get()
    {
        $query = $this->odata->from($this->type);
        if ($this->orders)
            $query->order($this->orders);
        if ($this->filter)
           $query->where($this->filter);
        if ($this->expand)
            $query = $query->expand($this->expand);
        if ($p = $this->pagination) {
            $query = $query->take($p['per_page'])
                ->skip($p['per_page'] * ($p['page'] - 1));
//                ->where('Id', '>', 0);   // Обходим баг в сервисе интеграции DRX. Без этого условия параметры 'take' и 'skip' не работают
        }
        $list = $query->get();
        return $list;
    }

    public function paginate($perPage = 20)
    {
        $query = $this->odata->from($this->type);
        if ($this->filter)
            foreach ($this->filter as $criteria)
                $query = $query->where($criteria[0], $criteria[1]);
        $total = $query->count();

        $pagination = [];
        $pagination["total"] = $total;
        $page = Request::input('page', 1);
        $pagination["page"] = $page;
        $pagination["per_page"] = Request::input('per_page', $perPage);
        $pagination["last_page"] = (int)ceil($total / $pagination["per_page"]);
        $pagination["first_page_url"] = http_build_query(array_merge(request()->all(), ["page" => 1]));
        $pagination["last_page_url"] = http_build_query(array_merge(request()->all(), ["page" => $pagination["last_page"]]));
        $pagination["prev_page_url"] = $page > 0 ? http_build_query(array_merge(request()->all(), ["page" => $page - 1])) : "";
        $pagination["next_page_url"] = $page < $total ? http_build_query(array_merge(request()->all(), ["page" => $page + 1])) : "";
        $this->pagination = $pagination;
        return $this;
    }

    public function GetPaginator() {
        return $this->pagination;
    }

    public function callAPIfunction($functionName, $params)
    {
        $Entity = $this->from($functionName)->post($params);
        return $Entity;
    }

    // Преобразует оператор сортировки формата Orchid ("-Name") в оператор сортировки формата OdataClient (['Name', 'desc'])
    public static function OrchidToOdataOrder($order)
    {
        if (!$order) return '';
        $order_field = $order;
        $order_dir = 'Asc';
        if ($order_field[0] == '-') {
            $order_field = substr($order_field, 1);
            $order_dir = 'Desc';
        }
        $order_field = str_replace('.', '/', $order_field);

        return [$order_field, $order_dir];
    }

    // Заготовка преобразования оператора фильтрации формата Orchid (['field1' => 'value1', 'field2' => 'value2'])
    // в оператор фильтрации формата OdataClint ([['field1', 'value2], ['field2', 'value2']]
    public static function OrchidToOdataFilter($filter)
    {
        if (!is_array($filter)) return [];
        $result = [];
        foreach ($filter as $field => $value) {
            $field = str_replace('.', '/', $field);
            $result[] = [$field, $value];
        }
        return $result;
    }


}


?>
