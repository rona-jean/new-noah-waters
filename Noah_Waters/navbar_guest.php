<?php
//di na need ng session check dito kasi para sa guest tong navigationbar
?>
<nav class="guest-navbar">
  <style>
    .guest-navbar {
      background: #4576a8;
      border-radius: 16px;
      margin: 20px auto 30px auto;
      padding: 10px 0;
      width: 95vw;
      max-width: 1200px;
      display: flex;
      justify-content: center;
      box-sizing: border-box;
    }
    .guest-navbar-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      padding: 0 20px;
    }
    .guest-navbar-brand {
      display: flex;
      align-items: center;
    }
    .guest-navbar-logo-img {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 10px;
      background: #fff;
      border: 2px solid #fff;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
    .guest-navbar-logo-text {
      font-weight: bold;
      color: #fff;
      font-size: 1.2em;
      letter-spacing: 1px;
    }
    .guest-navbar-links {
      display: flex;
      align-items: center;
      gap: 24px;
    }
    .guest-nav-link {
      color: #fff;
      text-decoration: none;
      font-size: 1.1em;
      transition: background 0.2s;
      padding: 8px 18px;
      border-radius: 10px;
      position: relative;
    }
    .guest-nav-link.active,
    .guest-nav-link:hover {
      font-weight: bold;
      background: #22395a;
      color: #fff;
    }
    @media (max-width: 768px) {
      .guest-navbar-inner {
        flex-direction: column;
        align-items: center;
        gap: 15px;
      }
      .guest-navbar-links {
        flex-direction: column;
        width: 100%;
        text-align: center;
        gap: 10px;
      }
      .guest-nav-link {
        width: 100%;
        display: block;
      }
    }
  </style>
  <div class="guest-navbar-inner">
    <div class="guest-navbar-brand">
      <img src="logo.jpg" alt="Noah Waters Logo" class="guest-navbar-logo-img" />
      <span class="guest-navbar-logo-text">Noah Waters</span>
    </div>
    <div class="guest-navbar-links">
      <a href="index.html" class="guest-nav-link<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? ' active' : '' ?>">Home</a>
      <a href="menu.php" class="guest-nav-link<?= basename($_SERVER['PHP_SELF']) == 'menu.php' ? ' active' : '' ?>">Menu</a>
      <a href="new_cart.php" class="guest-nav-link<?= basename($_SERVER['PHP_SELF']) == 'new_cart.php' ? ' active' : '' ?>">Cart</a>
      <a href="track_order_guest.php" class="guest-nav-link<?= basename($_SERVER['PHP_SELF']) == 'track_order_guest.php' ? ' active' : '' ?>">Track Order</a>
    </div>
  </div>
</nav> 