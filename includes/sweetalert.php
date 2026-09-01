<!-- SweetAlert2 Library -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// SweetAlert success function
function showSuccess(message) {
    Swal.fire({
        title: "Success!",
        text: message,
        icon: "success",
        confirmButtonColor: "#2E75B6",
        confirmButtonText: "OK"
    });
}

// SweetAlert error function
function showError(message) {
    Swal.fire({
        title: "Error!",
        text: message,
        icon: "error",
        confirmButtonColor: "#2E75B6",
        confirmButtonText: "OK"
    });
}

// SweetAlert warning function
function showWarning(message, callback) {
    Swal.fire({
        title: "Warning!",
        text: message,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#e74c3c",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Yes, proceed",
        cancelButtonText: "Cancel"
    }).then((result) => {
        if (result.isConfirmed && callback) {
            callback();
        }
    });
}

// SweetAlert info function
function showInfo(message) {
    Swal.fire({
        title: "Information",
        text: message,
        icon: "info",
        confirmButtonColor: "#2E75B6",
        confirmButtonText: "OK"
    });
}
</script>