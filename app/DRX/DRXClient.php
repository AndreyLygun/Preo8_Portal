<?php
namespace App\DRX;

use GuzzleHttp\Exception\GuzzleException;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\IProcessor;
use function LaravelLang\Locales\Enums\collection;

function conditionalDD($var)
{
    if (request()->has('test')) {
        dd($var);
    }
}


class PostProcessor implements IProcessor
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
class DRXClient extends ODataClient
{
    public function __construct()
    {
        $url = config('srq.url');
        $login = Auth()->user()->DrxAccount->DRX_Login;
        $password = Auth()->user()->DrxAccount->DRX_Password;
        //dd($url, $login, $password);
        parent::__construct($url, function ($request) use ($login, $password) {
            $request->headers['Authorization'] = 'Basic ' . base64_encode($login . ':' . $password);
        });
        $this->postProcessor = new PostProcessor();
    }

    public function getEntity($EntityType, int $Id, $ExpandFields)
    {
        $query = $this->from($EntityType);
        if ($ExpandFields)
            $query = $query->expand($ExpandFields);
        $entity = $query->find($Id);
        return $entity;
    }

    public function saveEntity($EntityType, $Entity, $ExpandFields = [], $CollectionFields = [])
    {
        $Id = isset($Entity['Id']) ? (int)$Entity['Id']:null;
        unset($Entity['Id']);
        unset($Entity['Renter']);
        // API Directum требует, чтобы в Id было целое число, а не строка
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
                // ..потом очищаем поле на сервере DRX, чтоб заполнить его новыми значениями
                if ($Id) $this->delete("{$EntityType}({$Id})/$cf");
            }
        }
        if ($Id) {            // Обновляем запись
            $Entity = ($this->from($EntityType)->expand($ExpandFields)->whereKey($Id)->patch($Entity))[0];
        } else {            // Создаём запись
            $Entity = ($this->from($EntityType)->expand($ExpandFields)->post($Entity))[0];
        }
        return $Entity;
    }

    public function deleteEntity($DRXEntity, int $Id)
    {
        $this->from($DRXEntity)->whereKey($Id)->delete();
    }

    public function getList($DRXEntity, $ExpandFields = [], $orderBy = '', $perPage = 100000)
    {
        try {
            $total = $this->from($DRXEntity)->count();
            $p = $this->pagination($total, $perPage);
            $request = $this->from($DRXEntity)
                ->take($p['per_page'] * $p['page'])
                ->skip($p['per_page'] * ($p['page'] - 1))
                ->where('Id', '>', 0)   // Обходим баг в сервисе интеграции DRX. Без этого условия параметры 'take' и 'skip' не работают
                ->order($this->OrderBy($orderBy));
            if ($ExpandFields) $request = $request->expand($ExpandFields);
            $entities = $request->get();
        } catch (GuzzleException $e) {
            return [
                'error' => [
                    'message' => $e->getMessage(),
                    'errnum' => $e->getCode()
                ]
            ];
        }
        return [
            "entities" => collect($entities),
            "pagination" => $p
        ];
    }


    // выбираем из строки запроса параметр sort и преваращаем его в параметры для odata-order
    protected function OrderBy($orderBy = 'Id')
    {
        $order_field = request()->get('sort', $orderBy);
        $order_dir = 'Asc';
        if ($order_field[0] == '-') {
            $order_field = substr($order_field, 1);
            $order_dir = 'Desc';
        }
        $order_field = str_replace('.', '/', $order_field);
        return [$order_field, $order_dir];
    }

    // Преобразовывает массив из ['Id'=>$Id, 'Name' => $Name] в [$Id => $Name]
    public function CollectKeyValue($Id = 'Id', $Name = 'Name')
    {
        $arr = $this->toArray();
        return array_reduce($arr,
            function ($carry, $item) use ($Id, $Name) {
                $carry[$item[$Id]] = $item[$Name];
                return $carry;
            });
    }

    public function pagination($total, $perPage = 10)
    {
        $pagination = [];
        $pagination["total"] = $total;
        $page = request("page") ?? 1;
        $pagination["page"] = $page;
        $pagination["per_page"] = request("per_page") ?? $perPage;
        $pagination["last_page"] = (int)ceil($total / $pagination["per_page"]);
        $pagination["first_page_url"] = http_build_query(array_merge(request()->all(), ["page" => 1]));
        $pagination["last_page_url"] = http_build_query(array_merge(request()->all(), ["page" => $pagination["last_page"]]));
        $pagination["prev_page_url"] = $page > 0 ? http_build_query(array_merge(request()->all(), ["page" => $page - 1])) : "";
        $pagination["next_page_url"] = $page < $total ? http_build_query(array_merge(request()->all(), ["page" => $page + 1])) : "";
        return $pagination;
    }

    public function callAPIfunction($functionName, $params)
    {
        $Entity = $this->from($functionName)->post($params);
        return $Entity;
    }
}


?>
