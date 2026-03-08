/*
Template Name: Velzon - Admin & Dashboard Template
Author: Themesbrand
Website: https://Themesbrand.com/
Contact: Themesbrand@gmail.com
File: CRM-companies Js File
*/


// list js
var checkAll = document.getElementById("checkAll");
if (checkAll) {
    checkAll.onclick = function () {
        var checkboxes = document.querySelectorAll('.form-check-all input[type="checkbox"]');
        var checkedCount = document.querySelectorAll('.form-check-all input[type="checkbox"]:checked').length;
        for (var i = 0; i < checkboxes.length; i++) {
            checkboxes[i].checked = this.checked;
            if (checkboxes[i].checked) {
                checkboxes[i].closest("tr").classList.add("table-active");
            } else {
                checkboxes[i].closest("tr").classList.remove("table-active");
            }
        }

        (checkedCount > 0) ? document.getElementById("remove-actions").style.display = 'none' : document.getElementById("remove-actions").style.display = 'block';
    };
}

var perPage = 10;
var editlist = false;

//Table
var options = {
    valueNames: [
        "id",
        "licensee",
        "classification",
        "subfolder",
        "version",
        "departmentname",
        "status"
    ],
    page: perPage,
    pagination: true,
    plugins: [
        ListPagination({
            left: 2,
            right: 2
        })
    ]
};
// Init list
var companyList = new List("companyList", options).on("updated", function (list) {
    list.matchingItems.length == 0 ?
        (document.getElementsByClassName("noresult")[0].style.display = "block") :
        (document.getElementsByClassName("noresult")[0].style.display = "none");
    var isFirst = list.i == 1;
    var isLast = list.i > list.matchingItems.length - list.page;
    // make the Prev and Nex buttons disabled on first and last pages accordingly
    (document.querySelector(".pagination-prev.disabled")) ? document.querySelector(".pagination-prev.disabled").classList.remove("disabled") : '';
    (document.querySelector(".pagination-next.disabled")) ? document.querySelector(".pagination-next.disabled").classList.remove("disabled") : '';
    if (isFirst) {
        document.querySelector(".pagination-prev").classList.add("disabled");
    }
    if (isLast) {
        document.querySelector(".pagination-next").classList.add("disabled");
    }
    if (list.matchingItems.length <= perPage) {
        document.querySelector(".pagination-wrap").style.display = "none";
    } else {
        document.querySelector(".pagination-wrap").style.display = "flex";
    }

    if (list.matchingItems.length > 0) {
        document.getElementsByClassName("noresult")[0].style.display = "none";
    } else {
        document.getElementsByClassName("noresult")[0].style.display = "block";
    }
});









var table = document.getElementById("customerTable");
// save all tr
var tr = table.getElementsByTagName("tr");
var trlist = table.querySelectorAll(".list tr");

var count = 11;
var forms = document.querySelectorAll('.tablelist-form')

var classificationFilter = document.getElementById("classificationFilter");
var statusFilter = document.getElementById("statusFilter");
var searchInput = document.getElementById("searchText");

function filterData() {
    var classificationVal = classificationFilter ? classificationFilter.value : 'all';
    var statusVal = statusFilter ? statusFilter.value.toLowerCase() : 'all';
    var searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';

    companyList.filter(function (item) {
        // Strip HTML tags for clean values
        var itemClassification = (item.values().classification || '').toString().replace(/<[^>]*>?/gm, '').trim();
        var itemStatus = (item.values().status || '').toString().replace(/<[^>]*>?/gm, '').trim().toLowerCase();

        var licenseeField = (item.values().licensee || '').toString().replace(/<[^>]*>?/gm, '').trim().toLowerCase();
        var versionField = (item.values().version || '').toString().replace(/<[^>]*>?/gm, '').trim().toLowerCase();

        var matchClassification = (classificationVal === 'all') || (itemClassification === classificationVal.trim());
        var matchStatus = (statusVal === 'all') || (itemStatus === statusVal.trim());

        var matchSearch = true;
        if (searchVal !== '') {
            matchSearch = (licenseeField.indexOf(searchVal) !== -1 || versionField.indexOf(searchVal) !== -1);
        }

        return matchClassification && matchStatus && matchSearch;
    });
}

if (classificationFilter) classificationFilter.addEventListener("change", filterData);
if (statusFilter) statusFilter.addEventListener("change", filterData);

if (searchInput) {
    searchInput.addEventListener("keyup", function () {
        // Clear any built-in search so our custom filter takes full control
        companyList.search('');
        filterData();
    });
}

document.querySelector(".pagination-next").addEventListener("click", function () {
    (document.querySelector(".pagination.listjs-pagination")) ? (document.querySelector(".pagination.listjs-pagination").querySelector(".active")) ?
        document.querySelector(".pagination.listjs-pagination").querySelector(".active").nextElementSibling.children[0].click() : '' : '';
});
document.querySelector(".pagination-prev").addEventListener("click", function () {
    (document.querySelector(".pagination.listjs-pagination")) ? (document.querySelector(".pagination.listjs-pagination").querySelector(".active")) ?
        document.querySelector(".pagination.listjs-pagination").querySelector(".active").previousSibling.children[0].click() : '' : '';
});