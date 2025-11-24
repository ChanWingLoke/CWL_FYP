// =============================================== //
//  AJAX REQUESTS (MINIFIED FILE REFORMATTED)      //
// =============================================== //

// --- CATEGORY: EDIT SUBMISSION ---
$("#editCatForm").submit(function(e) {
    e.preventDefault();
    var t = $("#editCatForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/edit_cat.php",
        data: t,
        success: function(e) {
            alert(e);
        }
    });
});

// --- CATEGORY: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#catagoryDelete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");

    if (confirm("Are you sure want to delete this item?")) {
        $.post("app/action/delete_cat.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == $.trim(e)) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert("failed to delete data");
            }
        });
    }
});

// --- MEMBER: ADD SUBMISSION ---
$("#adMemberForm").submit(function(e) {
    e.preventDefault();
    var t = $("#adMemberForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/add_member.php",
        data: t,
        success: function(e) {
            if ("yes" == $.trim(e)) {
                alert("member added successfully");
                location.reload();
            } else {
                alert(e);
            }
        }
    });
});

// --- MEMBER: EDIT SUBMISSION ---
$("#editMemberForm").submit(function(e) {
    e.preventDefault();
    var t = $("#editMemberForm").serialize();
    if (confirm("Are You sure want to edit data")) {
        $.ajax({
            type: "POST",
            url: "app/action/edit_member.php",
            data: t,
            success: function(e) {
                alert(e);
            }
        });
    } else {
        alert("your data are save");
    }
});

// --- MEMBER: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#memberDelete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");
    if (confirm("Are You sure want to delete this item?")) {
        $.post("app/action/delete_member.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == e) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert(e);
            }
        });
    }
});

// --- SUPPLIER: ADD SUBMISSION ---
$("#adsuppliarForm").submit(function(e) {
    e.preventDefault();
    var t = $("#adsuppliarForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/add_suppliar.php",
        data: t,
        success: function(e) {
            if ("yes" == $.trim(e)) {
                alert("suppliar added successfully.");
                location.reload();
            } else {
                alert(e);
            }
        }
    });
});

// --- SUPPLIER: EDIT SUBMISSION ---
$("#editSuppliarForm").submit(function(e) {
    e.preventDefault();
    var t = $("#editSuppliarForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/edit_suppliar.php",
        data: t,
        success: function(e) {
            alert(e);
        }
    });
});

// --- SUPPLIER: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#suppliarDelete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");
    if (confirm("Are You sure want to delete this item?")) {
        $.post("app/action/delete_suppliar.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == e) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert(e);
            }
        });
    }
});

// --- PRODUCT/ASSET: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#productDelete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");
    if (confirm("Are You sure want to delete this item?")) {
        $.post("app/action/delete_product.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == e) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert(e);
            }
        });
    }
});

// --- EXPENSE CATEGORY: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#ex_catagoryDelete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");
    if (confirm("Are You sure want to delete this item?")) {
        $.post("app/action/delete_exCaragroy.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == e) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert(e);
            }
        });
    }
});

// --- EXPENSE: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#expenseDelete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");
    if (confirm("Are You sure want to delete this item?")) {
        $.post("app/action/delete_expense.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == e) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert(e);
            }
        });
    }
});

// --- PRODUCT/ASSET: EDIT SUBMISSION ---
$("#editProduct").submit(function(e) {
    e.preventDefault();
    var t = $("#editProduct").serialize();
    if (confirm("Are you sure want to edit data")) {
        $.ajax({
            type: "POST",
            url: "app/action/edit_product.php",
            data: t,
            success: function(e) {
                alert(e);
            }
        });
    } else {
        alert("Your data is saved");
    }
});

// --- EXPENSE CATEGORY: ADD SUBMISSION ---
$("#addexpenseCat").submit(function(e) {
    e.preventDefault();
    var t = $("#addexpenseCat").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/addexpense_cat.php",
        data: t,
        success: function(e) {
            if ("yes" == $.trim(e)) {
                alert("Expense catagory added successfylly");
                location.reload();
            } else {
                alert(e);
            }
        }
    });
});

// --- EXPENSE: ADD SUBMISSION ---
$("#addExpenseForm").submit(function(e) {
    e.preventDefault();
    var t = $("#addExpenseForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/add_expense.php",
        data: t,
        success: function(e) {
            alert(e);
        }
    });
});

// --- EXPENSE: EDIT SUBMISSION ---
$("#editExpenseForm").submit(function(e) {
    e.preventDefault();
    var t = $("#editExpenseForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/edit_expense.php",
        data: t,
        success: function(e) {
            alert(e);
        }
    });
});

// --- STAFF: ADD SUBMISSION ---
$("#adstaffForm").submit(function(e) {
    e.preventDefault();
    var t = $("#adstaffForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/add_staff.php",
        data: t,
        success: function(e) {
            if ("yes" == $.trim(e)) {
                alert("Staff added successfully");
                $("#adstaffForm")[0].reset();
            } else {
                alert(e);
            }
        }
    });
});

// --- STAFF: EDIT SUBMISSION ---
$("#editstaffForm").submit(function(e) {
    e.preventDefault();
    var t = $("#editstaffForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/edit_staff.php",
        data: t,
        success: function(e) {
            alert(e);
        }
    });
});

// --- USER: UPDATE PROFILE SUBMISSION ---
$("#update_userForm").submit(function(e) {
    e.preventDefault();
    var t = $("#update_userForm").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/edit_update.php",
        data: t,
        success: function(e) {
            if ("yes" == $.trim(e)) {
                window.location.href = "app/action/logout.php"; // Redirect and log out on successful update
            } else {
                alert(e);
            }
        }
    });
});

// --- STAFF: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#staff_delete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");
    if (confirm("Are You sure want to delete this item?")) {
        $.post("app/action/delete_staff.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == $.trim(e)) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert("faild to delete data");
            }
        });
    }
});

// --- SMS: SEND SUBMISSION ---
$("#sendSmsForm").submit(function(e) {
    e.preventDefault();
    var t = $("#sms_number").val();
    var a = $("#sms_message").val();
    var d = $("#sendSmsForm").serialize();
    if ("" != t && "" != a) {
        $.ajax({
            type: "POST",
            url: "app/action/send_sms.php",
            data: d,
            success: function(e) {
                alert(e);
            }
        });
    } else {
        alert("All field must be filled out");
    }
});

// --- FACTORY PRODUCT: ADD SUBMISSION ---
$("#addFactoryProduct").submit(function(e) {
    e.preventDefault();
    var t = $("#addFactoryProduct").serialize();
    $.ajax({
        type: "POST",
        url: "app/action/add_factoryProduct.php",
        data: t,
        success: function(e) {
            alert(e);
        }
    });
});

// --- FACTORY PRODUCT: EDIT SUBMISSION ---
$("#editFactoryProduct").submit(function(e) {
    e.preventDefault();
    var t = $("#editFactoryProduct").serialize();
    if (confirm("Are You sure want to edit data")) {
        $.ajax({
            type: "POST",
            url: "app/action/edit_factoryProduct.php",
            data: t,
            success: function(e) {
                alert(e);
            }
        });
    } else {
        alert("your data are save");
    }
});

// --- FACTORY PRODUCT: DELETE BUTTON (Delegated Click) ---
$(document).on("click", "#factoryProductDelete_btn", function(e) {
    e.preventDefault();
    $delete_id = $(this).data("id");
    if (confirm("Are You sure want to delete this item?")) {
        $.post("app/action/delete_factoryProduct.php", {
            delete_id: $delete_id,
            delete_data: "delete_data"
        }, function(e) {
            if ("true" == $.trim(e)) {
                alert("data deleted successfull");
                location.reload();
            } else {
                alert("faild to delete data");
            }
        });
    }
});