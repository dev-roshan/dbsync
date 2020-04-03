
<a class="btn btn-primary p-btn" href="#" role="button" id="dbexport">Export <i class="fa fa-files-o" aria-hidden="true"></i>
</a>
<a href="#" id="file_download" target="_blank" download></a>
<p id="output"></p>


<script>
    $('#dbexport').on('click',function(){
        $('#db_export_overlay').show();
        var uri='/dbsync/export'
        $.ajax({
            url: uri,
            cache: false,
            success: function(data){
                // $('#output').html(data.data);
                $('#file_download').attr('href',data.file);
                $('#file_download')[0].click()
            },
            complete: function(){
                $('#db_export_overlay').hide();
            },
            error: function(err){
                alert(err.responseJSON.message);
            }
            });
    });
</script>


