document.addEventListener("DOMContentLoaded", () => {
    checkSession();
    initializeNavigation();
    initializeEventListeners();
    loadApplicants();
});

async function checkSession() {
    try {
        const response = await fetch("../apis/admin_auth.php?action=check_session");
        const result = await response.json();

        if (!result.success || !result.logged_in) {
            window.location.href = "login.html";
            return;
        }

        document.getElementById("admin-username").textContent = result.username;
    } catch (error) {
        console.error("Error checking session:", error);
        window.location.href = "login.html";
    }
}

function initializeNavigation() {
    const navLinks = document.querySelectorAll(".nav-link");
    const sections = document.querySelectorAll(".admin-section");

    navLinks.forEach(link => {
        link.addEventListener("click", (e) => {
            e.preventDefault();
            
            navLinks.forEach(l => l.classList.remove("active"));
            sections.forEach(s => s.classList.remove("active"));
            
            link.classList.add("active");
            const sectionId = link.getAttribute("data-section") + "-section";
            document.getElementById(sectionId).classList.add("active");
            
            if (link.getAttribute("data-section") === "applicants") {
                loadApplicants();
            } else if (link.getAttribute("data-section") === "marketers") {
                loadMarketers();
            } else if (link.getAttribute("data-section") === "logs") {
                loadLogs();
            }
        });
    });
}

function initializeEventListeners() {
    document.getElementById("logout-btn").addEventListener("click", logout);
    document.getElementById("refresh-applicants").addEventListener("click", loadApplicants);
    document.getElementById("refresh-marketers").addEventListener("click", loadMarketers);
    document.getElementById("add-marketer").addEventListener("click", showAddMarketerModal);
    document.getElementById("refresh-logs").addEventListener("click", loadLogs);
    
    document.querySelector(".close").addEventListener("click", closeModal);
    document.getElementById("modal").addEventListener("click", (e) => {
        if (e.target === document.getElementById("modal")) {
            closeModal();
        }
    });
}

async function logout() {
    try {
        const formData = new FormData();
        formData.append("action", "logout");
        
        const response = await fetch("../apis/admin_auth.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();
        
        if (result.success) {
            window.location.href = "login.html";
        }
    } catch (error) {
        console.error("Error during logout:", error);
        window.location.href = "login.html";
    }
}

async function loadApplicants() {
    try {
        const response = await fetch("../apis/admin_applicants.php");
        const result = await response.json();

        if (result.success) {
            displayApplicants(result.data);
        } else {
            alert("حدث خطأ في تحميل بيانات المتقدمين");
        }
    } catch (error) {
        console.error("Error loading applicants:", error);
        alert("حدث خطأ في الاتصال بالخادم");
    }
}

function displayApplicants(applicants) {
    const tbody = document.querySelector("#applicants-table tbody");
    tbody.innerHTML = "";

    const statusTranslations = {
        "pending": "قيد المراجعة",
        "approved": "مقبول",
        "rejected": "مرفوض",
        "interview": "مقابلة"
    };

    applicants.forEach(applicant => {
        const translatedStatus = statusTranslations[applicant.status] || applicant.status;
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${applicant.name}</td>
            <td>${applicant.age}</td>
            <td>${applicant.nationality}</td>
            <td>@${applicant.instagram || "غير متوفر"}</td>
            <td>${applicant.email}</td>
            <td>${applicant.phone}</td>
            <td>${translatedStatus}</td>
            <td>${new Date(applicant.created_at).toLocaleDateString("en-US")}م</td>
            <td>
                <button class="action-btn edit-btn" onclick="editApplicant(${applicant.id})">تعديل</button>
                <button class="action-btn delete-btn" onclick="deleteApplicant(${applicant.id})">حذف</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

async function loadMarketers() {
    try {
        const response = await fetch("../apis/admin_marketers.php");
        const result = await response.json();

        if (result.success) {
            displayMarketers(result.data);
        } else {
            alert("حدث خطأ في تحميل بيانات المسوقين");
        }
    } catch (error) {
        console.error("Error loading marketers:", error);
        alert("حدث خطأ في الاتصال بالخادم");
    }
}

function displayMarketers(marketers) {
    const tbody = document.querySelector("#marketers-table tbody");
    tbody.innerHTML = "";

    marketers.forEach(marketer => {
        const row = document.createElement("tr");
        const statusClass = marketer.is_active ? "status-active" : "status-inactive";
        const statusText = marketer.is_active ? "مفعل" : "غير مفعل";
        const toggleText = marketer.is_active ? "إلغاء التفعيل" : "تفعيل";
        const toggleClass = marketer.is_active ? "toggle-btn inactive" : "toggle-btn";
        
        row.innerHTML = `
            <td>${marketer.name}</td>
            <td>${marketer.email}</td>
            <td>${marketer.phone}</td>
            <td>${marketer.commission_rate}%</td>
            <td>${marketer.payment_method || "-"}</td>
            <td class="${statusClass}">${statusText}</td>
            <td>${marketer.start_date || "-"}</td>
            <td>
                <button class="action-btn edit-btn" onclick="editMarketer(${marketer.id})">تعديل</button>
                <button class="action-btn ${toggleClass}" onclick="toggleMarketerStatus(${marketer.id}, ${marketer.is_active ? 0 : 1})">${toggleText}</button>
                <button class="action-btn delete-btn" onclick="deleteMarketer(${marketer.id})">حذف</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

async function loadLogs(page = 1) {
    try {
        const response = await fetch(`../apis/admin_logs.php?page=${page}&limit=50`);
        const result = await response.json();

        if (result.success) {
            displayLogs(result.data);
            displayPagination(result.pagination);
        } else {
            alert("حدث خطأ في تحميل السجلات");
        }
    } catch (error) {
        console.error("Error loading logs:", error);
        alert("حدث خطأ في الاتصال بالخادم");
    }
}

function displayLogs(logs) {
    const tbody = document.querySelector("#logs-table tbody");
    tbody.innerHTML = "";

    logs.forEach(log => {
        const row = document.createElement("tr");
        row.innerHTML = `
            <td>${log.username || "غير محدد"}</td>
            <td>${log.action_type}</td>
            <td>${log.description}</td>
            <td>${log.ip_address}</td>
            <td>${new Date(log.timestamp).toLocaleString("ar-SA")}</td>
        `;
        tbody.appendChild(row);
    });
}

function displayPagination(pagination) {
    const paginationDiv = document.getElementById("logs-pagination");
    paginationDiv.innerHTML = "";

    if (pagination.total_pages > 1) {
        if (pagination.current_page > 1) {
            const prevBtn = document.createElement("button");
            prevBtn.textContent = "السابق";
            prevBtn.onclick = () => loadLogs(pagination.current_page - 1);
            paginationDiv.appendChild(prevBtn);
        }

        const currentPage = document.createElement("span");
        currentPage.className = "current-page";
        currentPage.textContent = `${pagination.current_page} من ${pagination.total_pages}`;
        paginationDiv.appendChild(currentPage);

        if (pagination.current_page < pagination.total_pages) {
            const nextBtn = document.createElement("button");
            nextBtn.textContent = "التالي";
            nextBtn.onclick = () => loadLogs(pagination.current_page + 1);
            paginationDiv.appendChild(nextBtn);
        }
    }
}

function showAddMarketerModal() {
    const modalBody = document.getElementById("modal-body");
    modalBody.innerHTML = `
        <h3>إضافة مسوق جديد</h3>
        <form id="add-marketer-form">
            <div class="form-group">
                <label for="marketer-name">الاسم:</label>
                <input type="text" id="marketer-name" name="name" required>
            </div>
            <div class="form-group">
                <label for="marketer-email">البريد الإلكتروني:</label>
                <input type="email" id="marketer-email" name="email" required>
            </div>
            <div class="form-group">
                <label for="marketer-phone">رقم الهاتف:</label>
                <input type="tel" id="marketer-phone" name="phone" required>
            </div>
            <div class="form-group">
                <label for="marketer-password">كلمة المرور:</label>
                <input type="password" id="marketer-password" name="password" required>
            </div>
            <div class="form-group">
                <label for="marketer-commission">نسبة العمولة (%):</label>
                <input type="number" id="marketer-commission" name="commission_rate" step="0.01" min="0" max="100">
            </div>
            <div class="form-group">
                <label for="marketer-payment">طريقة الدفع:</label>
                <input type="text" id="marketer-payment" name="payment_method">
            </div>
            <div class="form-actions">
                <button type="submit">إضافة</button>
                <button type="button" class="cancel-btn" onclick="closeModal()">إلغاء</button>
            </div>
        </form>
    `;

    document.getElementById("add-marketer-form").addEventListener("submit", handleAddMarketer);
    document.getElementById("modal").style.display = "block";
}

async function handleAddMarketer(e) {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append("action", "create");

    try {
        const response = await fetch("../apis/admin_marketers.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert("تم إضافة المسوق بنجاح");
            closeModal();
            loadMarketers();
        } else {
            alert(result.message || "حدث خطأ أثناء إضافة المسوق");
        }
    } catch (error) {
        console.error("Error adding marketer:", error);
        alert("حدث خطأ في الاتصال بالخادم");
    }
}

async function deleteApplicant(id) {
    if (!confirm("هل أنت متأكد من حذف هذا المتقدم؟")) {
        return;
    }

    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", id);

    try {
        const response = await fetch("../apis/admin_applicants.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert("تم حذف المتقدم بنجاح");
            loadApplicants();
        } else {
            alert(result.message || "حدث خطأ أثناء الحذف");
        }
    } catch (error) {
        console.error("Error deleting applicant:", error);
        alert("حدث خطأ في الاتصال بالخادم");
    }
}

async function deleteMarketer(id) {
    if (!confirm("هل أنت متأكد من حذف هذا المسوق؟")) {
        return;
    }

    const formData = new FormData();
    formData.append("action", "delete");
    formData.append("id", id);

    try {
        const response = await fetch("../apis/admin_marketers.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert("تم حذف المسوق بنجاح");
            loadMarketers();
        } else {
            alert(result.message || "حدث خطأ أثناء الحذف");
        }
    } catch (error) {
        console.error("Error deleting marketer:", error);
        alert("حدث خطأ في الاتصال بالخادم");
    }
}

async function toggleMarketerStatus(id, newStatus) {
    const formData = new FormData();
    formData.append("action", "toggle_status");
    formData.append("id", id);
    formData.append("is_active", newStatus);

    try {
        const response = await fetch("../apis/admin_marketers.php", {
            method: "POST",
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert(result.message);
            loadMarketers();
        } else {
            alert(result.message || "حدث خطأ أثناء تغيير الحالة");
        }
    } catch (error) {
        console.error("Error toggling marketer status:", error);
        alert("حدث خطأ في الاتصال بالخادم");
    }
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
}

function editApplicant(id) {
    alert("وظيفة التعديل ستكون متاحة قريباً");
}

function editMarketer(id) {
    alert("وظيفة التعديل ستكون متاحة قريباً");
}


