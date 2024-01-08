/** function to create a status modal */
function createStatusModal(mode, title, body)
{
    // send the data to create the status modal
    var xmlhttp = new XMLHttpRequest();
    xmlhttp.open("POST", "ajax/misc/getStatusModal.php", true);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.onreadystatechange = function() 
    {
        if (this.readyState == 4 && this.status == 200)
        {
            document.getElementById(mode+"-status-modal-div").innerHTML = this.responseText;     

            // display the edit customer modal
            $("#"+mode+"StatusModal").modal("show");
        }
    };
    xmlhttp.send("mode="+mode+"&title="+title+"&body="+body);
}

/** print a number with commas */
function numberWithCommas(num) 
{
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/* ************************************************
 *  TABLE PAGE CHANGE FUNCTIONS
 * ********************************************** */
/** function to go to the next page of the table */
function goToNextPage(table_id)
{
    let table = $("#"+table_id).DataTable();
    table.page("next").draw(false);      
}

/** function to go to the previous page of the table */
function goToPrevPage(table_id)
{
    let table = $("#"+table_id).DataTable();
    table.page("previous").draw(false);    
}

/** function to go to the last page of the table */
function goToLastPage(table_id)
{
    let table = $("#"+table_id).DataTable();
    
    // set page selection dropdown to last value
    let pl = table.page.len();
    let rows = table.page.info()["recordsTotal"];
    let totalPages = Math.ceil(rows / pl);
    let psd = document.getElementById(table_id+"-DT_PageSelection");
    psd.value = totalPages;
    
    table.page("last").draw(false);
}

/** function to go to the first page of the table */
function goToFirstPage(table_id)
{
    let table = $("#"+table_id).DataTable();
    table.page("first").draw(false);    
}

/** function to jump to the page selected */
function jumpToPage(table_id)
{
    let table = $("#"+table_id).DataTable();
    let page = document.getElementById(table_id+"-DT_PageSelection").value;
    table.page(parseInt(page - 1)).draw(false);
}

/** function to update the page selection dropdown */
function updatePageSelection(table_id, showPageCount = true)
{
    let tableE = $("#"+table_id).DataTable();
    let pl = tableE.page.len();

    let pi = tableE.page.info()["page"] + 1; // current page index + 1
    let totalPages = tableE.page.info()["pages"]; // number of total pages
    let filtered_records = tableE.page.info()["recordsDisplay"]; // number of filtered records
    let total_records = tableE.page.info()["recordsTotal"]; // number of total records

    // create dropdown page selection element
    let selectionHTML = document.getElementById(table_id+"-DT_PageChange");
    let selectionDIV = "";
    if (showPageCount === true) { selectionDIV = "Page <select class='form-select d-inline w-auto py-1' id='"+table_id+"-DT_PageSelection' onchange='jumpToPage(\""+table_id+"\", \"DT_PageSelection\")'>"; }
    else { selectionDIV = "<select class='form-select d-inline w-auto py-1' id='"+table_id+"-DT_PageSelection' onchange='jumpToPage(\""+table_id+"\", \"DT_PageSelection\")'>"; }
    if (totalPages < 0) { totalPages = 1; }
    for (let p = 1; p <= totalPages; p++)
    {
        if (pi == p) { selectionDIV += "<option value='" + p + "' selected>" + p + "</option>"; }
        else { selectionDIV += "<option value='" + p + "'>" + p + "</option>"; }
    }
    if (showPageCount === true) { selectionDIV += "</select> of " + totalPages; }
    else { selectionDIV += "</select>"; }
    selectionHTML.innerHTML = selectionDIV;

    // update details in footer
    let start = tableE.page.info()["start"] + 1;
    let end = tableE.page.info()["end"];

    if (filtered_records == total_records)
    {
        var detailsHTML = document.getElementById(table_id+"-DT_Details");
        var detailsDIV = "Showing " + start + " to " + end + " of " + total_records + " total entries";
    }
    else
    {
        var detailsHTML = document.getElementById(table_id+"-DT_Details");
        var detailsDIV = "Showing " + start + " to " + end + " of " + filtered_records + " filtered entries ("+ total_records+ " total entries)";
    }
    detailsHTML.innerHTML = detailsDIV;
}

/** function to change page length */
function updatePageLength(table_id, storage)
{
    let table = $("#"+table_id).DataTable();
    let pl = document.getElementById(table_id+"-DT_PageLength").value;
    table.page.len(pl).draw(false);

    // update session storage page length value
    sessionStorage[storage] = pl;
}
/* ************************************************
 *  END TABLE PAGE CHANGE FUNCTIONS
 * ********************************************** */

/** 
 *  function to get a cookie
 *  source: https://www.w3schools.com/js/js_cookies.asp
*/
function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for(let i = 0; i <ca.length; i++) 
    {
        let c = ca[i];
        while (c.charAt(0) == ' ') 
        {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}