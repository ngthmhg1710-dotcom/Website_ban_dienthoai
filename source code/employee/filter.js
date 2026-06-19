function updateDates() {
    const filter = document.getElementById("filter").value;
    const today = new Date();
    const start_date = document.getElementById("start_date");
    const end_date = document.getElementById("end_date");

    if (filter == "today") {
        start_date.value = today.toISOString().split("T")[0]; // Set today's date for start
        end_date.value = today.toISOString().split("T")[0]; // Set today's date for end
    } else if (filter == "yesterday") {
        today.setDate(today.getDate() - 1);
        start_date.value = today.toISOString().split("T")[0];
        end_date.value = today.toISOString().split("T")[0];
    } else if (filter == "7days") {
        today.setDate(today.getDate() - 7);
        start_date.value = today.toISOString().split("T")[0];
        end_date.value = new Date().toISOString().split("T")[0]; // End date is today
    } else if (filter == "this_month") {
        start_date.value = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split("T")[0]; // First day of this month
        end_date.value = new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0).toISOString().split("T")[0]; // Last day of this month
    } else if (filter == "custom") {
        start_date.value = '';
        end_date.value = '';
    }
}
