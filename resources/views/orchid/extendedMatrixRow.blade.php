<tr>
    @foreach($columns as $column)

        <th class="p-0 align-middle">
            {!!
               $fields[$column]
                    ->value($row[$column] ?? '')
                    ->prefix($name)
                    ->id("$idPrefix-$key-$column")
                    ->name($keyValue ? $column : "[$key][$column]")->readonly($readonly)
            !!}
        </th>

        @if ($loop->last && $removableRows && !$readonly)
            <th class="no-border text-center align-middle">
                <a href="#"
                   data-action="matrix#deleteRow"
                   class="small text-muted"
                   title="Remove row">
                    <x-orchid-icon path="bs.trash3"/>
                </a>
            </th>
        @endif
    @endforeach
</tr>
