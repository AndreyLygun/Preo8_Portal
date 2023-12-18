
<div>
    <nav aria-label="Page navigation example">
        <ul class="pagination">
            <li class="page-item"><a class="page-link" href="?{{$pagination['first_page_url']}}">&lt;&lt;</a></li>
            @if($pagination["page"]>1)
                <li class="page-item"><a class="page-link" href="?{{$pagination['prev_page_url']}}">&lt;</a></li>
            @endif
            <li class="page-item"><a class="page-link disabled">Стр. {{$pagination["page"]}} из {{$pagination["last_page"]}}</a></li>
            @if($pagination['page']<$pagination["last_page"])
            <li class="page-item"><a class="page-link" href="?{{$pagination['next_page_url']}}">&gt;</a></li>
            @endif
            <li class="page-item"><a class="page-link" href="?{{$pagination["last_page_url"]}}">&gt;&gt;</a></li>
        </ul>
    </nav>
</div>
