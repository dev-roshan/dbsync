{{-- @extends('dbsync::layout') --}}
@include('dbsync::layout')


{{-- @section('content') --}}
<style>
#db_import_overlay {
    position: fixed;
    width: 100%;
    height: 100%;
    background: black url("vendor/dbsync/img/loading.gif") center center no-repeat;
    opacity: .5;
}
</style>

<div id="db_import_overlay" hidden></div>

<form action="{{ route('dbsync_import') }}" id="import_form" method="POST" enctype="multipart/form-data" >
    {{ csrf_field() }}
    <div class="dbsync_group">
        <input name="file" type="file" id="file" class="dbsync_file_input" value="" accept="application/zip" required>
        <a class="btn btn-primary p-btn" href="#" role="button" id="dbsync_bnt">Check 
        </a>
        <a class="btn btn-success p-btn" href="#" role="button" id="dbimport" disabled>Import <i class="fa fa-files-o" aria-hidden="true"></i>
      </a>
      <p id="unzip_file_path" hidden></p>
        <p>*zip file only</p>
    </div>
    
</form>
<a href="#" id="log_file" target="_blank" download></a>
<p id="output"></p>
{{-- <a class="btn btn-primary p-btn" href="#" role="button" id="dbsync" >Sync <i class="fa fa-refresh" aria-hidden="true"></i></a> --}}
<!-- <a href="#" id="sync" target="_blank" download></a> -->
{{-- <p id="output"></p> --}}
<style>
 .dbsync_group{
    width: 25%;
    /* border: 2px solid gray; */
    padding: 5px;
 }
 .dbsync_file_input{
    border: 1px solid darkgray;
    padding: 2px;
 }

 .dbsync_sync_btn{
    margin-left: 10px;
 }

</style>

<script>
   $("#dbsync_bnt").click(function (event) {
   // required file validation
   if($("#file")[0].checkValidity()){
   //stop submit the form, we will post it manually.
   event.preventDefault();

   // Get form
   var form = $('#import_form')[0];

   // Create an FormData object 
   var data = new FormData(form);

   // If you want to add an extra field for the FormData
   // data.append("CustomField", "This is some extra data, testing");

   // disabled the submit button
   $("#dbsync_bnt").prop("disabled", true);
   $('#db_import_overlay').show();

   $.ajax({
      type: "POST",
      enctype: 'multipart/form-data',
      url: "{{ route('dbsync_check') }}",
      data: data,
      processData: false,
      contentType: false,
      cache: false,
      success: function (data) {
         if(data.error){
            $('#log_file').attr('href',data.logfile);
            $('#log_file')[0].click();
            alert('Check the downloaded log file.')
         }
         else{
            $('#unzip_file_path').val(data.unzip_path);
            $('#dbimport').attr('disabled',false);
            alert(data.check);

         }

         // $("#result").text(data);
         // console.log("SUCCESS : ", data);
         // $("#btnSubmit").prop("disabled", false);

      },
      complete:function(){
                $('#db_import_overlay').hide();
         },
      error: function (e) {
         alert(e.responseJSON.message);
         // $("#result").text(e.responseText);
         // console.log("ERROR : ", e);
         // $("#btnSubmit").prop("disabled", false);

      }
   });
   }
   else{
      $('#file')[0].reportValidity();
   }

   });


   $("#dbimport").click(function (event) {
      $('#db_import_overlay').show();
      var filepath = $('#unzip_file_path').val();
      $.ajax({
      type: "POST",
      url: "{{ route('dbsync_import') }}",
      data: {data:filepath},
      success: function (data) {
       if(data.error){
          alert(data.message);
       }
       else{

       }
       
      },
      complete:function(){
            $('#db_import_overlay').hide();
         },
      error: function (e) {
         alert(e.responseJSON.message);
         // $("#result").text(e.responseText);
         // console.log("ERROR : ", e);
         // $("#btnSubmit").prop("disabled", false);

      }
   });
   })


</script>



{{-- @endsection --}}

{{-- @include('dbsync::layout') --}}
