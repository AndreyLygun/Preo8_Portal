
<div>
    <nav aria-label="Page navigation example">
        <ul class="pagination">
            <li class="page-item"><a class="page-link" href="?page=1">Первая</a></li>
            @if($pagination["page"]>3)
            <li class="page-item"><a class="page-link" href="#">{{$pagination["page"]-2}}</a></li>
            @endif
            @if($pagination["page"]>2)
            <li class="page-item"><a class="page-link" href="#">{{$pagination["page"]-1}}</a></li>
            @endif
            <li class="page-item"><a class="page-link" href="#">{{$pagination["page"]}}</a></li>
            @if($pagination["last_page"]-$pagination['page']>0)
            <li class="page-item"><a class="page-link" href="#">{{$pagination["page"]+1}}</a></li>
            @endif
            @if($pagination["last_page"]-$pagination['page']>1)
            <li class="page-item"><a class="page-link" href="#">{{$pagination["page"]+2}}</a></li>
            @endif
            <li class="page-item"><a class="page-link" href="?page={{$pagination["last_page"]}}">Последняя</a></li>
        </ul>
    </nav>

    <!--nav aria-label="Page navigation example">
        <ul class="pagination">
            <li class="page-item">
                <a class="page-link" href="?{{$pagination["first_page_url"]}}">1</a>
            </li>
            @if($pagination["page"]!=1)
            <li class="page-item">
                <a class="page-link" href="?page={{$pagination["page"]-2}}">{{$pagination["page"]-2}}</a>
            </li>
            @endif

            @if($pagination["page"]>3)
            <li class="page-item"><a class="page-link" href="?page={{$pagination["page"]-2}}">{{$pagination["page"]-2}}</a></li>
            @endif
            @if($pagination["page"]>2)
            <li class="page-item"><a class="page-link" href="?page={{$pagination["page"]-1}}">{{$pagination["page"]-1}}</a></li>
            @endif
            <li class="page-item disabled active"><a class="page-link" href="?page={{$pagination["page"]}}">{{$pagination["page"]}}</a></li>
            @if($pagination["page"]<$pagination["last_page"])
            <li class="page-item"><a class="page-link" href="?page={{$pagination["page"]+1}}">{{$pagination["page"]+1}}</a></li>
            @endif
            @if($pagination["page"]<$pagination["last_page"]-1)
            <li class="page-item"><a class="page-link" href="?page={{$pagination["page"]+2}}">{{$pagination["page"]+2}}</a></li>
            @endif
            <li class="page-item"><a class="page-link" href="?{{$pagination["last_page_url"]}}">Последняя</a></li>
        </ul>
    </nav0-->
    <pre>{{ print_r($pagination) }}</pre>
</div>
