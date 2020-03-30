{{-- @extends('dbsync::layout') --}}


{{-- @section('content') --}}
<form action="{{ route('dbsync_import') }}" id="import_form" method="POST" enctype="multipart/form-data" >
    {{ csrf_field() }}
    <div class="dbsync_group">
        <input name="file" type="file" id="file" class="dbsync_file_input" value="" accept="application/zip" required>
        <a class="btn btn-primary p-btn" href="#" role="button" id="dbsync_bnt">Import 
        </a>
      
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
    border: 2px solid gray;
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

   $.ajax({
      type: "POST",
      enctype: 'multipart/form-data',
      url: "{{ route('dbsync_import') }}",
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
         // $("#result").text(data);
         // console.log("SUCCESS : ", data);
         // $("#btnSubmit").prop("disabled", false);

      },
      error: function (e) {
         alert(e.responseJSON);
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

</script>



{{-- @endsection --}}

{{-- @include('dbsync::layout') --}}
