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
            <tr class="add-row">
                <th colspan="{{ count($columns) }}" class="text-center p-0">
                    <a href="#" data-action="matrix#addRow" class="btn btn-block small text-muted">
                        <x-orchid-icon path="bs.plus-circle" class="me-2"/>

                        <span>{{ __('Add row') }}</span>
                    </a>
                </th>
            </tr>
        @endif
        <template class="matrix-template">
            @include('orchid.extendedMatrixRow',['row' => [], 'key' => '{index}'])
        </template>
        </tbody>
    </table>
@endcomponent
