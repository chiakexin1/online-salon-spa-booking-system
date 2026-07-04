<?php
include("../db.php");

// Fetch dynamic locations
$locations = mysqli_query($conn, "SELECT DISTINCT location FROM services");
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Service Catalogue</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php include '../header.php'; ?>

    <div class="container">
        <h1>Service Catalogue</h1>

        <!-- Filters -->
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-bottom:16px;">
            <input type="text" id="search" placeholder="Search services..."
                   style="flex:1; min-width:200px; padding:10px 14px; font-size:15px; border:1px solid #d1d5db; border-radius:8px;">

            <select id="location" style="padding:10px 12px; font-size:15px; border:1px solid #d1d5db; border-radius:8px;">
                <option value="">-- All Locations --</option>
                <?php while ($row = mysqli_fetch_assoc($locations)): ?>
                    <option value="<?= htmlspecialchars($row['location']) ?>">
                        <?= htmlspecialchars($row['location']) ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select id="service_type" style="padding:10px 12px; font-size:15px; border:1px solid #d1d5db; border-radius:8px;">
                <option value="">-- All Service Types --</option>
                <option value="hair">Hair</option>
                <option value="skincare">Skincare</option>
                <option value="massage">Massage</option>
                <option value="nails">Nails</option>
            </select>

            <select id="availability" style="padding:10px 12px; font-size:15px; border:1px solid #d1d5db; border-radius:8px;">
                <option value="">-- Availability --</option>
                <option value="available">Available</option>
                <option value="unavailable">Unavailable</option>
            </select>
        </div>
    </div>

    <hr>

    <div id="results" class="card-grid"></div>

    <!-- Modal -->
    <div class="modal" id="modal">
        <div class="modal-inner">
            <div id="modalContent"></div>
            <button id="closeModal">Close</button>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {

        const modal    = document.getElementById("modal");
        const closeBtn = document.getElementById("closeModal");

        function viewService(id) {
            fetch("view_service.php?id=" + id)
                .then(res => res.text())
                .then(data => {
                    document.getElementById("modalContent").innerHTML = data;
                    modal.classList.add("open");
                })
                .catch(err => console.error(err));
        }
        window.viewService = viewService;

        closeBtn.addEventListener("click", () => {
            modal.classList.remove("open");
        });

        function applyFilters() {
            const filters = {
                search:       document.getElementById("search").value,
                location:     document.getElementById("location").value,
                service_type: document.getElementById("service_type").value,
                availability: document.getElementById("availability").value
            };

            fetch("filter.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(filters)
            })
            .then(res => res.json())
            .then(data => displayResults(data));
        }

        function displayResults(data) {
            const grid = document.getElementById("results");

            if (data.length === 0) {
                grid.innerHTML = `
                    <div style="grid-column:1/-1; text-align:center; padding:60px 20px; color:#aaa;">
                        <p style="font-size:1.1rem;">No services found.</p>
                    </div>`;
                return;
            }

            // ✅ FIX: removed the stray semicolon that was after the closing backtick
            grid.innerHTML = data.map(service => {
                const originalPrice   = parseFloat(service.price);
                const promoPercent    = parseInt(service.promotion) || 0;
                const discountedPrice = promoPercent > 0
                    ? (originalPrice * (1 - promoPercent / 100)).toFixed(2)
                    : null;

                const priceDisplay = discountedPrice
                    ? `<s style="color:#aaa; font-size:0.88rem;">RM ${originalPrice.toFixed(2)}</s>
                       <strong style="color:#2e7d32; font-size:1rem;"> RM ${discountedPrice}</strong>
                       <span style="background:#e8f5e9; color:#2e7d32; padding:2px 7px; border-radius:10px; font-size:0.75rem; margin-left:4px;">
                           🏷 ${promoPercent}% OFF
                       </span>`
                    : `<strong>RM ${originalPrice.toFixed(2)}</strong>`;

                const availBadge = service.availability === 'available'
                    ? `<span style="background:#d4edda; color:#155724; padding:2px 8px; border-radius:10px; font-size:0.75rem;">✅ Available</span>`
                    : `<span style="background:#f8d7da; color:#721c24; padding:2px 8px; border-radius:10px; font-size:0.75rem;">❌ Unavailable</span>`;

                return `
                <div class="card">
                    <div class="card-image">
                        <img src="uploads/${service.image}" alt="${service.service_name}">
                    </div>
                    <div class="card-body">
                        <h3>${service.service_name}</h3>
                        <p class="muted">${service.service_type} • ${service.duration} mins</p>
                        <p style="margin:6px 0;">
                            ${priceDisplay}
                        </p>
                        <p style="margin:4px 0; font-size:0.88rem; color:#555;">
                            📍 ${service.location}
                        </p>
                        <p style="margin:6px 0;">${availBadge}</p>
                        <button onclick="viewService(${service.service_id})">View Details</button>
                    </div>
                </div>`;
            }).join("");  // ✅ .join("") with no separator — no stray characters between cards
        }

        document.getElementById("search").addEventListener("input", applyFilters);
        document.querySelectorAll("select").forEach(el => {
            el.addEventListener("change", applyFilters);
        });

        applyFilters();
    });
    </script>
</body>
</html>