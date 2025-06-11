// Admin Panel JavaScript

document.addEventListener("DOMContentLoaded", () => {
  // Mobile sidebar toggle
  const sidebarToggle = document.getElementById("sidebar-toggle")
  const sidebar = document.querySelector(".sidebar")

  if (sidebarToggle) {
    sidebarToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active")
    })
  }

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll(".alert")
  alerts.forEach((alert) => {
    setTimeout(() => {
      alert.style.opacity = "0"
      setTimeout(() => {
        alert.remove()
      }, 300)
    }, 5000)
  })

  // Confirm delete actions
  const deleteLinks = document.querySelectorAll('a[href*="delete="]')
  deleteLinks.forEach((link) => {
    link.addEventListener("click", (e) => {
      if (!confirm("Apakah Anda yakin ingin menghapus item ini?")) {
        e.preventDefault()
      }
    })
  })

  // Form validation
  const forms = document.querySelectorAll("form")
  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      const requiredFields = form.querySelectorAll("[required]")
      let isValid = true

      requiredFields.forEach((field) => {
        if (!field.value.trim()) {
          field.style.borderColor = "#dc3545"
          isValid = false
        } else {
          field.style.borderColor = "#e1e5e9"
        }
      })

      if (!isValid) {
        e.preventDefault()
        alert("Harap isi semua field yang wajib diisi!")
      }
    })
  })

  // Search form auto-submit on Enter
  const searchInputs = document.querySelectorAll('input[name="search"]')
  searchInputs.forEach((input) => {
    input.addEventListener("keypress", function (e) {
      if (e.key === "Enter") {
        this.closest("form").submit()
      }
    })
  })

  // Table row highlighting
  const tableRows = document.querySelectorAll(".data-table tbody tr")
  tableRows.forEach((row) => {
    row.addEventListener("mouseenter", function () {
      this.style.backgroundColor = "#f8f9fa"
    })

    row.addEventListener("mouseleave", function () {
      this.style.backgroundColor = ""
    })
  })

  // Number input validation
  const numberInputs = document.querySelectorAll('input[type="number"]')
  numberInputs.forEach((input) => {
    input.addEventListener("input", function () {
      if (this.value < 0) {
        this.value = 0
      }
    })
  })

  // Auto-resize textareas
  const textareas = document.querySelectorAll("textarea")
  textareas.forEach((textarea) => {
    textarea.addEventListener("input", function () {
      this.style.height = "auto"
      this.style.height = this.scrollHeight + "px"
    })
  })
})

// Utility functions
function showAlert(message, type = "success") {
  const alert = document.createElement("div")
  alert.className = `alert alert-${type}`
  alert.textContent = message

  const contentBody = document.querySelector(".content-body")
  if (contentBody) {
    contentBody.insertBefore(alert, contentBody.firstChild)

    setTimeout(() => {
      alert.style.opacity = "0"
      setTimeout(() => {
        alert.remove()
      }, 300)
    }, 5000)
  }
}

function formatNumber(num) {
  return new Intl.NumberFormat("id-ID").format(num)
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return date.toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  })
}

// Export functions for global use
window.AdminPanel = {
  showAlert,
  formatNumber,
  formatDate,
}
