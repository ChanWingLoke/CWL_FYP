
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Assets</h1>
        </div><!-- /.col -->
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Assets</li>
          </ol>
        </div><!-- /.col -->
      </div><!-- /.row -->
    </div><!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->

  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><b>Assets List</b></h3>
          <!-- Keep the same link so routing/AJAX doesn’t break; just change the label -->
          <a href="index.php?page=add_product" target="_blank"
             class="btn btn-primary btn-sm float-right rounded-0" style="margin:8px;">
            <i class="fas fa-plus"></i> New Asset
          </a>
        </div>

        <!-- /.card-header -->
        <div class="card-body">
          <div class="table-responsive">
            <!-- Keep the same ID to avoid breaking any existing JS initializers -->
            <table id="productTable" class="display dataTable text-center" style="width:100%">
              <thead>
                <tr>
                  <th>Asset ID</th>
                  <th>Asset Name</th>
                  <th>Brand</th>
                  <th>Category</th>
                  <th>Source</th>
                  <!-- CHANGED casing only -->
                  <th>Quantity</th>
                  <!-- Optional label tweaks; safe to keep as-is if you prefer -->
                  <th>Buying Price</th>
                  <th>Selling Price</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <!-- tbody likely populated via JS/AJAX elsewhere -->
            </table>
          </div>
        </div>
        <!-- /.card-body -->
      </div>
    </div>
  </section>
</div>

<script>
$(function(){
  $('#assetsTable').DataTable({
    pageLength: 25,
    order: [[1, 'asc']]
  });
});
</script>