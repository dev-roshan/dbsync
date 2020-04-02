
<link rel="stylesheet" href="vendor/dbsync/css/bootstrap-3.4.1.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<script src="vendor/dbsync/js/jquery.min.js"></script>
<script src="vendor/dbsync/js/bootstrap.min.js"></script>

<style>
#db_export_overlay {
    position: fixed;
    width: 100%;
    height: 100%;
    background: black url("vendor/dbsync/img/loading.gif") center center no-repeat;
    opacity: .5;
}
</style>

<div id="db_export_overlay" hidden></div>

<div class="container">
    @include('dbsync::export')
    @include('dbsync::import')
</div>
