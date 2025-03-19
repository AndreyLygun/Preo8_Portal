@component($typeForm, get_defined_vars())
    <table class="matrix table table-bordered border-right-0"
           data-controller="matrix"
           data-matrix-index="{{ $index }}"
           data-matrix-rows="{{ $maxRows }}"
           data-matrix-key-value="{{ var_export($keyValue) }}"
    >
        <thead>
        <tr>
            @foreach($columns as $key => $column)
                <th scope="col">
                    {{ is_int($key) ? $column : $key }}
                </th>
            @endforeach
        </tr>
        </thead>
        <tbody>

        @foreach($value as $key => $row)
            @include('orchid.extendedMatrixRow',['row' => $row, 'key' => $key])
        @endforeach

        @if(!($readonly??false))
            <tr class="add-row pt-3">
                <th colspan="{{ count($columns) }}" class="text-center p-0">
                    <div class="btn-group" role="group">
                        <a href="#" data-action="matrix#addRow" class="btn btn-outline btn-block small">
                            <span>{{ __('Add row') }}</span>
                        </a>
                        @foreach($extraButtons??[] as $button)
                            {!! $button->class('btn btn-outline btn-block small text-nowrap')->toHtml() !!}
                        @endforeach
                    </div>

                </th>
            </tr>
        @endif
        <template class="matrix-template">
            @include('orchid.extendedMatrixRow',['row' => [], 'key' => '{index}'])
        </template>
        </tbody>
    </table>
@endcomponent
