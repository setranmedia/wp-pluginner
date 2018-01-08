<h2>Configuration Details</h2>
<table class="widefat">
    <thead>
    <tr>
        <th class="row-title">
            Key
        </th>
        <th>
            Value(s)
        </th>
    </tr>
    </thead>
    <tbody>
    @php $count = count($config->all()); @endphp

    @foreach($config->all() as $key => $items)
        @if(count($items))
            <tr @if($count % 2 == 0) class="alternate" @endif>

                <td class="row-title">
                    <label for="tablecell">
                        {{$key}}
                    </label>
                </td>
                <td>
                    @if(is_array($items))
                        @foreach($items as $key2 => $items2)
                        @if(is_array($items2))
                            <strong>{{ $key2 }}: </strong>
                            @include('admin.framework.tabs.config-branch',['items' => $items2, 'branch' => 1])
                             <br/>

                        @elseif(is_bool($items2))
                            <strong>{{ $key2 }}: </strong> {{var_export($items2, true)}} <br/>
                        @elseif(!is_null($items2))
                            <strong>{{ $key2 }}: </strong> {{$items2}}<br/>
                        @endif

                        @endforeach

                    @elseif(is_bool($items))
                        {{var_export($items, true)}}  <br/>
                    @else
                        {{$items}}  <br/>
                    @endif
                </td>
            </tr>
        @endif
        @php
            $count--;
        @endphp
    @endforeach
    </tbody>
</table>
