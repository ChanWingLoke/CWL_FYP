<div class="content-wrapper">
  
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Assets</h1>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Assets</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><b>Assets List</b></h3>
          <a href="index.php?page=add_product"
             class="btn btn-primary btn-sm float-right rounded-0" style="margin:8px;">
            <i class="fas fa-plus"></i> New Asset
          </a>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="productTable" class="display dataTable text-center" style="width:100%">
              <thead>
                <tr>
                  <th>Asset ID</th>
                  <th>Asset Name</th>
                  <th>Brand</th>
                  <th>Category</th>
                  <th>Source</th>
                  <th>Quantity</th>
                  <!-- <th>Buying Price</th>
                  <th>Selling Price</th> -->
                  <th>Actions</th>
                </tr>
              </thead>
              </table>
          </div>
        </div>
        </div>
    </div>
  </section>
</div>

<script>
$(function(){
  // Initialize DataTables on the Assets table
  $('#productTable').DataTable({ 
    pageLength: 25,
    order: [[1, 'asc']]
  });
});
</script>