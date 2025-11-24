<div class="content-wrapper">
  
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0 text-dark">Edit Asset</h1> 
        </div>
        <div class="col-sm-6 mt-4">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="index.php?page=dashboard">Home</a></li>
            <li class="breadcrumb-item active">Edit Products</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
  <section class="content">
    <div class="container-fluid">
      <hr>
      
      <div class="row">
        <div class="col-md-12 col-lg-12 mt-3">
          <div class="card">
            
            <div class="card-body">
              <?php 
                // START: PHP Logic to fetch existing product data
                if (isset($_GET['edit_id'])) {
                  $edit_id = $_GET['edit_id'];
                  $data = $obj->find('products', 'id', $edit_id);

                  if ($data) {
                    // Data found, render the edit form
              ?>
              
              <form id="editProduct">
                <div class="row">
                  
                  <div class="col-md-4 col-lg-4">
                    <div class="form-group">
                      <label for="product_name">Product name * :</label>
                      <input type="text" class="form-control" id="product_name" name="product_name" value="<?=$data->product_name;?>">
                      <input type="text" hidden name="id" value="<?=$edit_id;?>">
                    </div>
                  </div>
                  
                  <div class="col-md-4 col-lg-4">
                    <div class="form-group">
                      <label for="brand">Brand Name * :</label>
                      <input type="text" class="form-control" id="brand" value="<?=$data->brand_name;?>" name="brand">
                    </div>
                  </div>
                  
                  <div class="col-md-4 col-lg-4">
                    <div class="form-group">
                      <label for="p_catagory">Product catagory * :</label>
                      <select name="p_catagory" id="p_catagory" class="form-control select2">
                        <option disabled selected>Select a catagory</option>
                        <?php 
                          $all_catgory = $obj->all('catagory');
                          $select_val = $data->catagory_id;

                          foreach ($all_catgory as $catagory) {
                            $selected = ($select_val == $catagory->id) ? 'selected' : '';
                        ?>  
                            <option <?php echo $selected;?> value="<?=$catagory->id;?>"><?=$catagory->name;?></option>
                        <?php 
                          } // End foreach
                        ?>
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-4 col-lg-4">
                    <div class="form-group">
                      <label for="product_source">Product source * :</label>
                      <select name="product_source" id="product_source" class="form-control select2">
                        <option <?php if ($data->product_source == 'factory'){echo "selected";} ?> value="factory">Factory</option>
                        <option <?php if ($data->product_source == 'buy'){echo "selected";} ?> value="buy">Buying</option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="col-md-4 col-lg-4">
                    <div class="form-group">
                      <label for="sku">SKU * :</label>
                      <input type="text" class="form-control" readonly id="sku" value="<?=$data->sku;?>" name="sku">
                    </div>
                  </div>
                  
                  <div class="col-md-4 col-lg-4">
                    <div class="form-group">
                      <label for="quantity">Quantity * :</label>
                      <input type="number" class="form-control" id="quantity" value="<?=$data->quantity;?>" name="quantity">
                    </div>
                  </div>
                  
                  </div>
                
                <div class="row text-center mt-5">
                  <div class="col-md-6 offset-md-3 col-lg-6 offset-lg-3">
                    <button type="submit" title="update data" class="btn btn-primary pl-5 pr-5  rounded-0">update</button>
                  </div>
                </div>
              </form>
              <?php 
                } else {
                  // Data not found in DB for the given ID
                  header("location:index.php?page=error_page");
                } // End if ($data)

              } // End if (isset($_GET['edit_id']))
              ?>
              
            </div>
          </div>
        </div>
      </div>
      
    </div>
  </section>
  </div>