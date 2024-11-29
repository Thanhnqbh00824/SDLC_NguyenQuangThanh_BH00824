<?php
require 'db/db.php';
session_start();

// Fetch all products with category names
$query = "SELECT products.*, categories.name AS category_name 
          FROM products 
          LEFT JOIN categories ON products.category_id = categories.id";
$stmt = $conn->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories for dropdowns
$query = "SELECT id, name FROM categories";
$stmt = $conn->query($query);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle add product form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category_id']);

    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_dir = 'uploads/products/';
        
        // Check if the directory exists, create it if not
        if (!is_dir($image_dir)) {
            mkdir($image_dir, 0777, true); // Create the directory with permissions
        }
        
        $image_name = basename($_FILES['image']['name']);
        $image_path = $image_dir . $image_name;

        // Validate the uploaded file
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $image = $image_path;
            } else {
                $error_message = "Failed to upload image.";
            }
        } else {
            $error_message = "Invalid image format. Allowed formats: jpg, jpeg, png, gif.";
        }
    }

    // Validate inputs
    if (empty($name) || $price <= 0 || empty($description) || $quantity <= 0 || $category_id <= 0) {
        $error_message = "Please fill in all fields with valid values.";
    } else {
        try {
            $query = "INSERT INTO products (name, price, description, quantity, category_id, image) 
                      VALUES (:name, :price, :description, :quantity, :category_id, :image)";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':description' => $description,
                ':quantity' => $quantity,
                ':category_id' => $category_id,
                ':image' => $image
            ]);
            $success_message = "Product added successfully!";
        } catch (PDOException $e) {
            $error_message = "Failed to add product: " . $e->getMessage();
        }
    }
}

// Handle edit product form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $id = intval($_POST['id']);
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $quantity = intval($_POST['quantity']);
    $category_id = intval($_POST['category_id']);

    // Handle image upload (for editing)
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_dir = 'uploads/products/';
        
        // Check if the directory exists, create it if not
        if (!is_dir($image_dir)) {
            mkdir($image_dir, 0777, true); // Create the directory with permissions
        }
        
        $image_name = basename($_FILES['image']['name']);
        $image_path = $image_dir . $image_name;

        // Validate the uploaded file
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        if (in_array($file_extension, $allowed_extensions)) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                $image = $image_path;
            } else {
                $error_message = "Failed to upload image.";
            }
        } else {
            $error_message = "Invalid image format. Allowed formats: jpg, jpeg, png, gif.";
        }
    }

    // Validate inputs
    if (empty($name) || $price <= 0 || empty($description) || $quantity <= 0 || $category_id <= 0 || $id <= 0) {
        $error_message = "Please fill in all fields with valid values.";
    } else {
        try {
            $query = "UPDATE products 
                      SET name = :name, price = :price, description = :description, 
                          quantity = :quantity, category_id = :category_id, image = :image 
                      WHERE id = :id";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':description' => $description,
                ':quantity' => $quantity,
                ':category_id' => $category_id,
                ':image' => $image,
                ':id' => $id
            ]);
            $success_message = "Product updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Failed to update product: " . $e->getMessage();
        }
    }
}

// Handle delete product
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    try {
        $query = "DELETE FROM products WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':id' => $id]);

        $success_message = "Product deleted successfully!";
    } catch (PDOException $e) {
        $error_message = "Failed to delete product: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Product Management</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <header>
    <h1>Admin Panel - Product Management</h1>
    <nav>
      <ul>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </header>

  <section>
    <!-- Display Success and Error Messages -->
    <?php if (isset($success_message)): ?>
      <div class="success_message"><?php echo $success_message; ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
      <div class="error_message"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <h2>Product List</h2>
    <table>
      <thead>
        <tr>
          <th>Product Name</th>
          <th>Price</th>
          <th>Quantity</th>
          <th>Category</th>
          <th>Image</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
          <td><?php echo htmlspecialchars($product['name']); ?></td>
          <td>$<?php echo number_format($product['price'], 2); ?></td>
          <td><?php echo $product['quantity']; ?></td>
          <td><?php echo htmlspecialchars($product['category_name']); ?></td>
          <td>
            <?php if ($product['image']): ?>
              <img src="<?php echo $product['image']; ?>" alt="Product Image" width="100">
            <?php else: ?>
              No Image
            <?php endif; ?>
          </td>
          <td>
            <a href="#edit_<?php echo $product['id']; ?>" class="edit_btn" onclick="showEditForm(<?php echo $product['id']; ?>)">Edit</a> | 
            <a href="?delete=<?php echo $product['id']; ?>" class="delete_btn" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Add New Product Form -->
    <h2>Add New Product</h2>
    <form method="POST" action="admin.php" enctype="multipart/form-data">
      <label for="name">Product Name:</label>
      <input type="text" id="name" name="name" required>

      <label for="price">Price:</label>
      <input type="number" id="price" name="price" step="0.01" required>

      <label for="description">Description:</label>
      <textarea id="description" name="description" required></textarea>

      <label for="quantity">Quantity:</label>
      <input type="number" id="quantity" name="quantity" required>

      <label for="category_id">Category:</label>
      <select id="category_id" name="category_id" required>
        <?php foreach ($categories as $category): ?>
        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
        <?php endforeach; ?>
      </select>

      <label for="image">Image:</label>
      <input type="file" id="image" name="image">

      <button type="submit" name="add_product">Add Product</button>
    </form>
  </section>

</body>
</html>
