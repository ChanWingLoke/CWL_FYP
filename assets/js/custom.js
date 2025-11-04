// ======================= //
//  CUSTOM.JS FORMATTED    //
// ======================= //

function editAddNewRow() {
  $.ajax({
    url: "app/ajax/addNewRow.php",
    method: "POST",
    data: { getOrderItem: 1 },
    success: function (response) {
      $("#editInvoiceItem").append(response);
      $(".select2").select2();

      var i = 0;
      $(".si_number").each(function () {
        $(this).html(++i);
      });
    },
  });
}

// ========== MEMBER TABLE ========== //
$("#empTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/member_data.php" },
  columns: [
    { data: "member_id" },
    { data: "name" },
    { data: "company" },
    { data: "address" },
    { data: "con_num" },
    { data: "total_buy" },
    { data: "total_paid" },
    { data: "total_due" },
    { data: "action" },
  ],
});

// ========== SUPPLIER TABLE ========== //
$("#suppliarTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/suppliar_data.php" },
  columns: [
    { data: "suppliar_id" },
    { data: "name" },
    { data: "company" },
    { data: "address" },
    { data: "con_num" },
    { data: "total_buy" },
    { data: "total_paid" },
    { data: "total_due" },
    { data: "action" },
  ],
});

// ========== STAFF TABLE ========== //
$("#staffTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/staff_data.php" },
  columns: [
    { data: "id" },
    { data: "name" },
    { data: "designation" },
    { data: "con_no" },
    { data: "email" },
    { data: "address" },
    { data: "action" },
  ],
});

// ========== ADD CATEGORY FORM ========== //
$("#addCatForm").submit(function (e) {
  e.preventDefault();
  var formData = $("#addCatForm").serialize();

  $.ajax({
    type: "POST",
    url: "app/action/add_catagory.php",
    data: formData,
    success: function (response) {
      if ($.trim(response) == "yes") {
        alert("Category added successfully");
        location.reload();
      }
    },
  });
});

// ========== CATEGORY TABLES ========== //
$("#catagoryTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/catagory_data.php" },
  columns: [
    { data: "id" },
    { data: "name" },
    { data: "description" },
    { data: "action" },
  ],
});

$("#ex_catagoryTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/ex_catagory_data.php" },
  columns: [
    { data: "id" },
    { data: "name" },
    { data: "description" },
    { data: "action" },
  ],
});

// ========== ADD PRODUCT FORM ========== //
$("#addProduct").submit(function (e) {
  e.preventDefault();

  var product_name = $("#product_name").val();
  var brand = $("#brand").val();
  var category = $("#p_catagory").val();

  if (product_name != "" && brand != "" && category != null) {
    var data = $("#addProduct").serialize();
    $.ajax({
      type: "POST",
      url: "app/action/add_product.php",
      data: data,
      success: function (response) {
        if ($.trim(response) == "yes") {
          $(".addProductError-area").show();
          $("#addProductError").html("Product added successfully");
          $("#addProduct")[0].reset();
        } else {
          $(".addProductError-area").show();
          $("#addProductError").html(response);
        }
      },
    });
  } else {
    $(".addProductError-area").show();
    $("#addProductError").html("Please fill out all required fields");
  }
});

// ========== PRODUCT TABLES ========== //
$("#productTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/product_data.php" },
  columns: [
    { data: "product_id" },
    { data: "product_name" },
    { data: "brand_name" },
    { data: "catagory_name" },
    { data: "product_source" },
    { data: "quantity" },
    { data: "buy_price" },
    { data: "sell_price" },
    { data: "action" },
  ],
});

$("#otherProductTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/factoryProduct_data.php" },
  columns: [
    { data: "id" },
    { data: "product_id" },
    { data: "product_name" },
    { data: "brand_name" },
    { data: "catagory_name" },
    { data: "quantity" },
    { data: "product_expense" },
    { data: "sell_price" },
    { data: "action" },
  ],
});

// ========== PURCHASE TABLE ========== //
$("#purchaseTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/purchase_data.php" },
  columns: [
    { data: "id" },
    { data: "product_name" },
    { data: "purchase_date" },
    { data: "purchase_quantity" },
    { data: "purchase_price" },
    { data: "purchase_sell_price" },
    { data: "purchase_net_total" },
    { data: "purchase_due_bill" },
    { data: "return_status" },
    { data: "action" },
  ],
});

// ========== PURCHASE RETURN TABLE ========== //
$("#purchasereturnTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/purchase_return_data.php" },
  columns: [
    { data: "id" },
    { data: "sell_id" },
    { data: "suppliar_name" },
    { data: "return_date" },
    { data: "product_name" },
    { data: "return_quantity" },
    { data: "subtotal" },
    { data: "discount" },
    { data: "netTotal" },
  ],
});

// ========== SELL TABLE ========== //
$("#sellTable").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/sell_data.php" },
  columns: [
    { data: "id" },
    { data: "customer_name" },
    { data: "order_date" },
    { data: "sub_total" },
    { data: "prev_due" },
    { data: "net_total" },
    { data: "paid_amount" },
    { data: "due_amount" },
    { data: "return_status" },
    { data: "payment_type" },
    { data: "action" },
  ],
});

// ========== SELL RETURN TABLE ========== //
$("#sell_returnList").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/sell_return_data.php" },
  columns: [
    { data: "id" },
    { data: "customer_name" },
    { data: "invoice_id" },
    { data: "return_date" },
    { data: "amount" },
  ],
});

// ========== EXPENSE TABLE ========== //
$("#expenseList").DataTable({
  processing: true,
  serverSide: true,
  serverMethod: "post",
  ajax: { url: "app/ajax/expense_data.php" },
  columns: [
    { data: "id" },
    { data: "ex_date" },
    { data: "expense_for" },
    { data: "amount" },
    { data: "expense_cat" },
    { data: "ex_description" },
    { data: "action" },
  ],
});

// ======================== //
//   DOCUMENT READY LOGIC   //
// ======================== //
$(document).ready(function () {
  // --- Add new invoice row
  function addNewRow() {
    $.ajax({
      url: "app/ajax/addNewRow.php",
      method: "POST",
      data: { getOrderItem: 1 },
      success: function (response) {
        $("#invoiceItem").append(response);
        $(".select2").select2();

        var i = 0;
        $(".si_number").each(function () {
          $(this).html(++i);
        });
      },
    });
  }

  // --- Calculate totals
  function calcTotals(discount) {
    var subtotal = 0;
    var netTotal = 0;
    var prev_due = parseInt($("#prev_due").val());
    var disc = discount;

    $(".tprice").each(function () {
      subtotal += 1 * $(this).val();
      $("#netTotal").val(netTotal);
    });

    var discAmount = (subtotal / 100) * disc;
    $("#s_discount_amount").val(discAmount);

    netTotal = subtotal - discAmount + prev_due;

    $("#subtotal").val(subtotal);
    $("#netTotal").val(netTotal);
  }

  addNewRow();

  $("#addNewRowBtn").on("click", function (e) {
    e.preventDefault();
    addNewRow();
  });

  $(document).on("click", ".cancelThisItem", function (e) {
    e.preventDefault();
    $(this).parent().parent().remove();
    calcTotals(0);
  });

  $(document).on("change", ".pid", function (e) {
    e.preventDefault();
    var id = $(this).val();
    var row = $(this).parent().parent();

    $.ajax({
      url: "app/ajax/single_sell_item.php",
      method: "POST",
      dataType: "json",
      data: { getSellSingleInfo: 1, id: id },
      success: function (data) {
        row.find(".qaty").val(data.quantity);
        row.find(".oqty").val(1);
        row.find(".price").val(data.sell_price);
        row.find(".pro_name").val(data.product_name);
        row.find(".tprice").val(row.find(".oqty").val() * row.find(".price").val());
        calcTotals(0);
      },
    });
  });

  $(document).on("keyup", ".oqty", function (e) {
    var qty = $(this);
    var row = $(this).parent().parent();

    if (qty.val() - 0 > row.find(".qaty").val() - 0) {
      alert("Please enter a valid quantity");
    } else {
      row.find(".tprice").val(row.find(".oqty").val() * row.find(".price").val());
      calcTotals(0);
    }
  });

  $(document).on("change", "#customer_name", function (e) {
    e.preventDefault();
    var id = $("#customer_name").val();

    $.ajax({
      url: "app/ajax/find_customer_due.php",
      method: "POST",
      dataType: "json",
      data: { getcusTotalDue: 1, id: id },
      success: function (data) {
        $("#prev_due").val(data.total_due);
      },
    });
  });

  $("#discount").on("keyup", function (e) {
    e.preventDefault();
    calcTotals($(this).val());
  });

  $(document).on("keyup", ".price", function (e) {
    e.preventDefault();
    var row = $(this).parent().parent();
    var val = $(this).val();
    row.find(".tprice").val(val);
    calcTotals(0);
  });

  $(document).on("keyup", "#s_discount_amount", function (e) {
    e.preventDefault();
    var disc = $("#s_discount_amount").val();
    var subtotal = $("#subtotal").val() - disc;
    $("#netTotal").val(subtotal);
  });

  $("#paidBill").on("keyup", function (e) {
    e.preventDefault();
    var paid = $(this).val();
    var net = $("#netTotal").val() - paid;
    $("#dueBill").val(net);
  });

  $("#sellBtn").on("click", function (e) {
    e.preventDefault();
    $("#sellForm").serialize();

    var cust = $("#customer_name").val();
    var payMethode = $("#payMethode").val();

    if (cust != null && payMethode != null) {
      $.ajax({
        url: "app/action/sell.php",
        method: "POST",
        data: $("#sellForm").serialize(),
        success: function (response) {
          var id = response;
          if (!isNaN(id)) {
            window.location.href = "index.php?page=view_sell&&view_id=" + id;
          } else {
            alert("Failed to make sell. Please try again.");
          }
        },
      });
    } else {
      alert("You missed some required fields");
    }
  });
});

// ========== Edit Sell Button ========== //
$(document).on("click", "#editSellBtn", function (e) {
  e.preventDefault();

  var confirmEdit = confirm("Are you sure you want to edit this sell?");
  var payMethode = $("#payMethode").val();

  if (confirmEdit) {
    if (payMethode != null) {
      $.ajax({
        url: "app/action/edit_sell.php",
        method: "POST",
        data: $("#editSellForm").serialize(),
        success: function (response) {
          var id = response;
          if (!isNaN(id)) {
            window.location.href = "index.php?page=view_sell&&view_id=" + id;
          } else {
            alert(response);
          }
        },
      });
    } else {
      alert("Please select a payment method");
    }
  } else {
    alert("Your data are safe");
  }
});
