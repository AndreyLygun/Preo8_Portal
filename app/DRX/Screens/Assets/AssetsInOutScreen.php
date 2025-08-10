<?php

namespace App\DRX\Screens\Assets;

use App\DRX\Helpers\ImportExcel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Exceptions\LaravelExcelException;
use Orchid\Screen\Actions\Link;
use Orchid\Support\Facades\Alert;
use Orchid\Support\Color;
use Orchid\Support\Facades\Toast;
use App\DRX\Screens\SecuritySRQScreen;


class AssetsInOutScreen extends SecuritySRQScreen
{

    public static $EntityType = 'IServiceRequestsPass4AssetsMovings';
    public static $Title = 'Разовый ввоз-вывоз ТМЦ';
    protected static $ExpandFields = ['LoadingSite', 'Inventory', 'ElevatorTimeSpan($expand=Name)'];
    protected static $CollectionFields = ["Inventory", 'ElevatorTimeSpan'];


    public function query(int $id = null): iterable
    {
        $query = parent::query($id);
        if (isset($query["entity"]["ElevatorTimeSpan"])) {
            $ElevatorTimeSpans = ($query["entity"]["ElevatorTimeSpan"]);
            if (isset($ElevatorTimeSpans[0]['Name'])) {
                $ElevatorTimeSpan = collect($ElevatorTimeSpans)->map(fn($value) => $value["Name"]["Id"]);
                $query["entity"]["ElevatorTimeSpan"] = $ElevatorTimeSpan->toArray();
            }
        }
        return $query;
    }


    public function beforeSave()
    {
        parent::beforeSave();
        $this->NormalizeDate(['ValidOn']);
        if (isset($this->entity["ElevatorTimeSpan"]))
            $this->entity["ElevatorTimeSpan"] = collect($this->entity["ElevatorTimeSpan"])->map(fn($value) => (object)["Name" => (object)["Id" => (int)$value]])->toArray();
    }


    public function commandBar(): iterable
    {
        $buttons = parent::commandBar();
        if (isset($this->entity["Id"]))
            $buttons[] = Link::make('Копировать')->route(Route::currentRouteName(), ['fromId' => $this->entity['Id']]);
        return $buttons;
    }

    public function layout(): iterable
    {
        $layout = parent::layout();
        $readonly = $this->readOnly;
        $layout[] = AssetsInOutListener::class;

        return $layout;
    }

    public function saveCarrier(Request $request)
    {
        Toast::info("ok, saved");
        $validated = $request->validate([
            'entity.Id' => '',
            'entity.CarModel' => '',
            'entity.CarNumber' => '',
            'entity.Visitors' => ''
        ]);
        $this->SaveToDRX(false, $validated["entity"]);
    }

    public function ClearInventory(Request $request)
    {
        $this->entity = array_merge($this->entity, $request->input('entity') ?? []);
        $this->entity['Inventory'] = [];
    }

    public function FillFromExcell(Request $request)
    {
        $this->entity = array_merge($this->entity, request()->input('entity') ?? []);
        if (!$request->hasFile('ExcelFile')) return;
        try {
            $res = ImportExcel::Basic($request->file('ExcelFile'), ['Name', 'Size', 'Quantity', 'Note']);
            $this->entity['Inventory'] = $res;
            Toast::info('Данные из файла импортированы. Не забудьте сохранить заявку.');
        } catch (LaravelExcelException $ex) {
            Alert::error(stripcslashes($ex->getMessage()));
        }
    }
}
