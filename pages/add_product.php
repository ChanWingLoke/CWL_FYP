<div class="content-wrapper">
  
  <div class="content-header">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6">
          <h1 class="m-0 text-dark">Add New Asset</h1> 
        </div>
        <div class="col-md-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active">Add Asset</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title"><b>Add a new product (Asset)</b></h3>

          <button type="button" class="btn btn-primary btn-sm float-right rounded-0" data-toggle="modal" data-target=".catagoryModal">
            <i class="fas fa-plus"></i> New Category
          </button>
        </div>
        <div class="card-body">
          
          <div class="alert alert-primary alert-dismissible fade show addProductError-area" role="alert">
            <span id="addProductError"></span>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          
          <form id="addProduct">
            <div class="row">
              <div class="col-md-6 ">
                <div class="form-group">
                  <label for="product_name">Product name * :</label>
                  <input type="text" class="form-control" id="product_name" placeholder="Product name" name="product_name">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="brand">Brand Name * :</label>
                  <input type="text" class="form-control" id="brand" placeholder="Brand name" name="brand">
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="p_catagory">Product Category * :</label>
                  <select name="p_catagory" id="p_catagory" class="form-control select2">
                    <option disabled selected>Select a category</option>
                    <?php 
                      // PHP: Fetch all existing categories from the database and populate the dropdown dynamically.
                      $all_catgory = $obj->all('catagory');
                      foreach ($all_catgory as $catagory) {
                        ?>  
                          <option value="<?=$catagory->id;?>"><?=htmlspecialchars($catagory->name);?></option>
                        <?php 
                      }
                    ?>
                  </select>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="form-group">
                  <label for="product_source">Product source * :</label>
                  <select name="product_source" id="product_source" class="form-control select2">
                    <option value="factory">Factory</option>
                    <option value="buy">Buying</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="sku">SKU :</label>
                  <input type="text" class="form-control" id="sku" placeholder="Product SKU" name="sku">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="quantity">Quantity :</label>
                  <input type="number" class="form-control" id="quantity" placeholder="Product quantity" name="quantity">
                </div>
              </div>
            </div>
            
            <div class="row text-center buttons">
              <div class="col-md-6 offset-md-3 col-lg-6 offset-lg-3">
                <input type="reset" title="Reset form" class="btn btn-danger pl-5 pr-5 rounded-0">
                <button type="submit" title="Save data" class="btn btn-primary pl-5 pr-5  rounded-0">Submit</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div></section>
  </div>