<?php

namespace App\Orchid\Screens\DRX;

use App\DRX\DRXClient;
use Carbon\Carbon;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Orchid\Screen\Fields\CheckBox;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Support\Facades\Layout;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Matrix;
use App\DRX\ExtendedMatrix;
use Orchid\Screen\Fields\DateTimer;


class Pass4PermanentAssetsMovingScreen extends SecuritySRQScreen
{

    protected $EntityType = 'IServiceRequestsPass4PermanentAssetsMovings';
    protected $Title = 'Заявка на регулярное перемещение ТМЦ';

    public function ExpandFields()
    {
        return array_merge(parent::ExpandFields(), ['Cars']);
    }

    public function CollectionFields()
    {
        return array_merge(parent::CollectionFields(), ["Cars"]);
    }

    public function layout(): iterable
    {
        Log::debug("Начало layout()");
        $readonly = $this->entity['RequestState'] != 'Draft';
        $layout = parent::layout();
        $layout[] = Layout::rows([
            DateTimer::make('entity.ValidFrom')->title("Действует с")->horizontal()
                ->min(Carbon::today())->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            DateTimer::make('entity.ValidTill')->title("Действует до")->horizontal()
                ->min(Carbon::today())->enableTime(false)->format('Y-m-d')->serverFormat('Y-m-d\Z'),
            TextArea::make('entity.Assets')->title('Описание ТМЦ')->horizontal(),
            ExtendedMatrix::make('entity.Cars')
                ->columns(['Модель' => 'Model', 'Гос.гомер' => 'Number', 'Примечание' => 'Note'])
                ->readonly($readonly)
        ])->title("Описание ТМЦ");
        Log::debug("Конец layout()");
        return $layout;
    }
}
