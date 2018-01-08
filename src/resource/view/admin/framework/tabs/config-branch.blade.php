@php
    $indent = '';
    for($i=0; $i< $branch; $i++){
        $indent .= '&nbsp;&nbsp;&nbsp;';
    }
    $branch += 1;

@endphp

@foreach($items as $key => $item)
    @if(!is_null($item))
        <br/> {{ $indent }} {{ !$loop->last ? '&#9507;' : '&#9495;' }}

        @if(is_array($item))
             <strong>{{ $key }}: </strong> @include('admin.framework.tabs.config-branch',[ 'key' => $key, 'items' => $item, 'branch' => $branch ])
        @elseif(is_bool($item))
            <strong>{{ $key }}: </strong> {{var_export($item, true)}}
        @else
            <strong>{{ $key }}: </strong> {{$item}}
        @endif
    @endif

@endforeach
