<script type="text/javascript">
    jQuery(document).ready(function ($) {
        $('.{{ $slug.'_flush_sessions' }}').click(function (e) {
            e.preventDefault();
            $.post('{{admin_url('admin-ajax.php')}}', {
                'action': '{{ $slug }}',
                '_flush': 'sessions'
            }, function (response) {
                alert(response);
            });
        });
        $('.{{ $slug.'_flush_objects' }}').click(function (e) {
            e.preventDefault();
            $.post('{{admin_url('admin-ajax.php')}}', {
                'action': '{{ $slug }}',
                '_flush': 'objects'
            }, function (response) {
                alert(response);
            });
        });
        $('.{{ $slug.'_flush_routes' }}').click(function (e) {
            e.preventDefault();
            $.post('{{admin_url('admin-ajax.php')}}', {
                'action': '{{ $slug }}',
                '_flush': 'routes'
            }, function (response) {
                alert(response);
            });
        });
        $('.{{ $slug.'_flush_views' }}').click(function (e) {
            e.preventDefault();
            $.post('{{admin_url('admin-ajax.php')}}', {
                'action': '{{ $slug }}',
                '_flush': 'views'
            }, function (response) {
                alert(response);
            });
        });

    });
</script>
