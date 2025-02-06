<div class="bg-white rounded shadow-sm p-4 py-4 d-flex flex-column">
@foreach($reviewStatus as $block)
    @if ($block['Status'] == 'InProcess')
        <div><b>{{ $block['BlockName'] }}:</b> ожидает согласования с {{$block['CreatedAt']}}</div>
        <div><b>Согласующие:</b>
        @foreach($block['Performers'] as $performer)
            {{ $performer['Name'] }}{{$loop->last?"":", "}}
        @endforeach
        </div>
    @elseif($block["Status"] == 'Completed')
            @if($block['Result'] == 'Approved' or $block["Result"] == 'WithSuggestions')
                <div class="text-success">{{ $block["BlockName"] }}: cогласовано в {{$block['CompletedAt']}}</div>
                <div>Согласующий: {{$block['CompletedBy']["Name"]}}</div>
                <div><b>Комментарий:</b>
                    @foreach($block["Texts"] as $text)
                        {{ $text["Body"] }}{{$loop->last?"":", "}}
                    @endforeach
                </div>
            @endif
            @if($block["Result"] == 'ForRework')
                <div class = "text-danger">{{ $block['BlockName'] }}: отказано в {{$block['CompletedAt']}}</div>
                <div>Согласующий: {{$block['CompletedBy']['Name']}}</div>
                <div><b>Причина:</b>
                    @foreach($block["Texts"] as $text)
                        {{ $text["Body"] }}{{$loop->last?"":", "}}
                    @endforeach
                </div>
            @endif
        @endif
    <hr>
@endforeach
</div>

