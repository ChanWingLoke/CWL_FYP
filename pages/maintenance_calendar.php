<?php if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; } ?>
<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1 class="m-0 text-dark">Maintenance — Calendar</h1>
      <ol class="breadcrumb float-sm-right mb-0">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item active">Calendar</li>
      </ol>
    </div>
  </div>
  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0"><b>Maintenance Calendar</b></h3>
          <div class="small">
            <span class="badge" style="background:#ffc107">Scheduled</span>
            <span class="badge" style="background:#17a2b8">In Progress</span>
            <span class="badge" style="background:#28a745">Completed</span>
          </div>
        </div>
        <div class="card-body">
          <div id="maintenanceCalendar"></div>
        </div>
      </div>
    </div>
  </section>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var el = document.getElementById('maintenanceCalendar');
  if (!el) return;
  if (!window.FullCalendar) {
    el.innerHTML = '<div class="text-danger">FullCalendar failed to load.</div>';
    return;
  }
  var cal = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    height: 'auto',
    headerToolbar: { left:'prev,next today', center:'title', right:'dayGridMonth,timeGridWeek,timeGridDay,listWeek' },
    events: [] // next step: wire up app/action/maintenance_events.php
  });
  cal.render();
});
</script>
