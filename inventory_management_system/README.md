# 📦 INVENTORY MANAGEMENT SYSTEM
## Complete Stock Tracking & Automation

---

## 🎯 WHAT IT DOES:

### ✅ **Auto Stock Reduction**
- Automatically reduces stock when orders are placed
- Validates stock availability before checkout
- Prevents overselling (can't buy what's not in stock)
- Transaction-safe (rollback if stock update fails)

### ✅ **Stock Alerts**
- Low stock warnings in admin dashboard
- Out-of-stock notifications
- Restock alerts
- Real-time badge notifications

### ✅ **Stock History Tracking**
- Logs every stock change
- Tracks who made changes (admin ID)
- Records order references
- Notes and reasons for adjustments

### ✅ **Manual Stock Management**
- Admin can add/remove stock manually
- Bulk adjustments with notes
- Set custom low-stock thresholds
- Quick restock buttons

### ✅ **Inventory Reports**
- Total inventory value
- In-stock count
- Low stock count
- Out-of-stock count
- Product-level history

---

## 📁 FILES INCLUDED:

1. **inventory_system.sql** - Database tables
2. **inventory.php** - API functions
3. **process_order_with_inventory.php** - Updated order processor
4. **admin_inventory.php** - Admin inventory dashboard
5. **INVENTORY_README.md** - This file

---

## 🚀 INSTALLATION:

### STEP 1: Run Database Setup
```sql
-- Open phpMyAdmin
-- Select sp_gadgets database
-- Import: inventory_system.sql
```

**This creates:**
- `stock_history` table
- `stock_alerts` table  
- `low_stock_threshold` column in products

### STEP 2: Upload API Files
```
Upload to /sp-gadgets/api/:
- inventory.php (NEW)

Replace existing:
- process_order.php → process_order_with_inventory.php
```

### STEP 3: Upload Admin Page
```
Upload to /sp-gadgets/admin/:
- inventory.php (NEW)
```

### STEP 4: Update Admin Sidebar
Add this to `admin/includes/header.php` sidebar menu:
```php
<a href="inventory.php" class="menu-item <?php echo $currentPage === 'inventory' ? 'active' : ''; ?>">
    <i class="fas fa-warehouse"></i> Inventory
</a>
```

---

## ✨ FEATURES IN DETAIL:

### 1. **Auto Stock Reduction on Purchase**
When customer places order:
```
✅ Validates stock availability
✅ Reduces stock for each product
✅ Logs change in stock_history
✅ Creates alerts if low/out of stock
✅ Rollback if any step fails
```

### 2. **Stock Validation**
Before checkout completes:
```php
validateCartStock($cartItems)
Returns: ['valid' => bool, 'errors' => array]
```

**Prevents:**
- Overselling
- Negative stock
- Out-of-stock purchases

### 3. **Stock Alerts**
Automatic notifications when:
- Product goes out of stock (0 units)
- Product falls below threshold (default 10)
- Product is restocked (above threshold)

**Shown in:**
- Admin dashboard
- Inventory page
- Product list

### 4. **Stock History**
Tracks every change:
```
- Who: Admin ID or "order"
- What: Add/Remove/Purchase/Return
- When: Timestamp
- How much: Before/After quantities
- Why: Notes field
```

### 5. **Manual Adjustments**
Admin can:
- Add stock (+10, +50, +100)
- Remove stock (-5, -10)
- Set custom thresholds per product
- Add notes for each change

---

## 🎨 ADMIN INTERFACE:

### **Inventory Dashboard** (`/admin/inventory.php`)

**Statistics Cards:**
- 💰 Total Inventory Value
- ✅ In Stock Count
- ⚠️ Low Stock Count
- ❌ Out of Stock Count

**Alerts Section:**
- Unread stock alerts
- Product name
- Alert type (low/out/restocked)
- Timestamp
- Mark as read button

**Low Stock Table:**
- Products below threshold
- Current stock level
- Threshold value
- Quick restock button

**Out of Stock Table:**
- Products with 0 stock
- Category
- Urgent restock button

**Quick Adjustment Form:**
- Select product dropdown
- Adjustment input (+/-)
- Notes textarea
- Submit button

---

## 📊 DATABASE STRUCTURE:

### **stock_history** Table:
```sql
- id (PRIMARY)
- product_id (FK to products)
- change_type (purchase, manual_add, manual_subtract, return, adjustment)
- quantity_before
- quantity_change
- quantity_after
- reference_type (order, admin, return)
- reference_id
- notes
- created_at
```

### **stock_alerts** Table:
```sql
- id (PRIMARY)
- product_id (FK to products)
- alert_type (low_stock, out_of_stock, restocked)
- stock_level
- is_read
- created_at
```

### **products** Table (updated):
```sql
-- New column added:
- low_stock_threshold (INT, default 10)
```

---

## 🔧 API FUNCTIONS:

### Core Functions:
```php
updateStock($productId, $quantityChange, $changeType, $referenceType, $referenceId, $notes)
// Updates stock and logs history

reduceStockForOrder($orderId)
// Reduces stock for all items in order

restoreStockForOrder($orderId)
// Restores stock for cancelled orders

checkStockAvailability($productId, $quantity)
// Checks if sufficient stock exists

validateCartStock($cartItems)
// Validates entire cart has stock

getStockHistory($productId, $limit)
// Gets change history for product

getLowStockProducts()
// Gets all low stock products

getOutOfStockProducts()
// Gets all out of stock products

getUnreadStockAlerts()
// Gets unread alerts for admin
```

---

## 🛡️ SAFETY FEATURES:

### ✅ **Transaction Safety**
All stock updates use database transactions:
```php
$pdo->beginTransaction();
// ... update stock
// ... create order
// ... log history
$pdo->commit();
// OR rollback if any step fails
```

### ✅ **Prevents Overselling**
Stock is locked during order:
```sql
SELECT stock_quantity FROM products WHERE id = ? FOR UPDATE
```

### ✅ **Validates Before Purchase**
Checks stock BEFORE creating order:
```php
if (!checkStockAvailability($productId, $quantity)) {
    return error;
}
```

### ✅ **Atomic Operations**
All changes are logged atomically:
- Stock update
- History log
- Alert creation
All succeed or all fail together

---

## 📱 MOBILE RESPONSIVE:

✅ Works on all devices
✅ Touch-friendly interface
✅ Optimized tables
✅ Responsive cards

---

## 🎯 USAGE EXAMPLES:

### **Customer Places Order:**
1. Adds items to cart
2. Goes to checkout
3. System validates stock ✅
4. Order created
5. Stock reduced automatically ✅
6. History logged ✅
7. Alert created if low stock ✅

### **Admin Restocks Product:**
1. Goes to Inventory page
2. Sees low stock alert
3. Uses quick adjustment form
4. Adds +50 units
5. Stock updated ✅
6. History logged ✅
7. Alert cleared ✅

### **Order Cancelled:**
1. Admin cancels order
2. Stock restored automatically ✅
3. History logged ✅

---

## ⚠️ IMPORTANT NOTES:

1. **Always run SQL first** - Creates required tables
2. **Replace process_order.php** - Use the new version with inventory
3. **Backup database** - Before running SQL
4. **Test thoroughly** - Place test orders
5. **Set thresholds** - Adjust per product (default 10)

---

## ✅ WHAT YOU GET:

✅ Auto stock reduction on purchase
✅ Stock validation before checkout
✅ Low stock alerts
✅ Out-of-stock alerts
✅ Complete stock history
✅ Manual stock adjustments
✅ Inventory dashboard
✅ Real-time statistics
✅ Transaction safety
✅ Rollback on errors

---

## 🚀 READY TO USE!

1. Import SQL
2. Upload files
3. Test with order
4. Watch stock reduce automatically!

**Your inventory is now fully automated!** 📦💯
