touch index.php
<?php
echo "Hello, School Demo!";
?>
<?php
// Replace with your actual database credentials
$servername = "localhost";
$username = "username";
$password = "password";
$dbname = "school_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<?php
// Include config file
require_once "config.php";

// Fetch all students with class names using JOIN
$sql = "SELECT s.id, s.name, s.email, s.created_at, c.name AS class_name, s.image 
        FROM student s
        LEFT JOIN classes c ON s.class_id = c.class_id
        ORDER BY s.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home Page - School Demo</title>
    <link rel="stylesheet" href="styles.css"> <!-- Your CSS file -->
</head>
<body>
    <div class="container">
        <h1>Students</h1>
        <a href="create.php" class="btn">Add New Student</a>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Class</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['class_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <a href="view.php?id=<?php echo $row['id']; ?>">View</a> |
                        <a href="edit.php?id=<?php echo $row['id']; ?>">Edit</a> |
                        <a href="delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this student?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
<?php
// Include config file
require_once "config.php";

// Fetch classes for dropdown
$sql = "SELECT * FROM classes";
$classes_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Student - School Demo</title>
    <link rel="stylesheet" href="styles.css"> <!-- Your CSS file -->
</head>
<body>
    <div class="container">
        <h1>Create Student</h1>
        <form action="create_process.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address"></textarea>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_id" required>
                    <option value="">Select Class</option>
                    <?php while ($row = $classes_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['class_id']; ?>"><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Image</label>
                <input type="file" name="image" accept="image/jpeg, image/png" required>
            </div>
            <div class="form-group">
                <button type="submit" name="submit">Create Student</button>
                <a href="index.php" class="btn cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php
// Close connection
$conn->close();
?>
<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $class_id = $_POST['class_id'];
    
    // Image upload handling
    $targetDir = "uploads/";
    $fileName = basename($_FILES["image"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES["image"]["tmp_name"]);
    if($check !== false) {
        // Allow certain file formats
        if(in_array($fileType, array("jpg", "jpeg", "png"))) {
            // Upload file to server
            if(move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                // Insert student details into database
                $sql = "INSERT INTO student (name, email, address, class_id, image) 
                        VALUES (?, ?, ?, ?, ?)";
                
                if($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("sssis", $name, $email, $address, $class_id, $fileName);
                    if($stmt->execute()) {
                        header("location: index.php");
                        exit();
                    } else {
                        echo "Error: " . $stmt->error;
                    }
                }
                $stmt->close();
            } else {
                echo "Error uploading image.";
            }
        } else {
            echo "Only JPG, JPEG, PNG files are allowed.";
        }
    } else {
        echo "File is not an image.";
    }
} else {
    header("location: index.php");
    exit();
}

$conn->close();
?>
<?php
require_once "config.php";

if(isset($_GET['id'])) {
    $student_id = $_GET['id'];
    
    // Fetch student details with class name using JOIN
    $sql = "SELECT s.name, s.email, s.address, s.created_at, c.name AS class_name, s.image 
            FROM student s
            LEFT JOIN classes c ON s.class_id = c.class_id
            WHERE s.id = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $student_name = $row['name'];
            $email = $row['email'];
            $address = $row['address'];
            $class_name = $row['class_name'];
            $created_at = $row['created_at'];
            $image = $row['image'];
        } else {
            echo "Student not found.";
            exit();
        }
    } else {
        echo "Error fetching data.";
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Student - <?php echo htmlspecialchars($student_name); ?></title>
    <link rel="stylesheet" href="styles.css"> <!-- Your CSS file -->
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($student_name); ?></h1>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></p>
        <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
        <p><strong>Class:</strong> <?php echo htmlspecialchars($class_name); ?></p>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($created_at); ?></p>
        <p><strong>Image:</strong><br>
            <img src="uploads/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($student_name); ?>" class="thumbnail">
        </p>
        <p>
            <a href="edit.php?id=<?php echo $student_id; ?>" class="btn">Edit</a>
            <a href="delete.php?id=<?php echo $student_id; ?>" onclick="return confirm('Are you sure you want to delete this student?')" class="btn delete">Delete</a>
            <a href="index.php" class="btn">Back</a>
        </p>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
<?php
require_once "config.php";

if(isset($_GET['id'])) {
    $student_id = $_GET['id'];
    
    // Fetch student details with class name using JOIN
    $sql = "SELECT s.name, s.email, s.address, s.class_id, c.name AS class_name, s.image 
            FROM student s
            LEFT JOIN classes c ON s.class_id = c.class_id
            WHERE s.id = ?";
    
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $student_name = $row['name'];
            $email = $row['email'];
            $address = $row['address'];
            $class_id = $row['class_id'];
            $class_name = $row['class_name'];
            $image = $row['image'];
        } else {
            echo "Student not found.";
            exit();
        }
    } else {
        echo "Error fetching data.";
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}

// Fetch classes for dropdown
$sql = "SELECT * FROM classes";
$classes_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - <?php echo htmlspecialchars($student_name); ?></title>
    <link rel="stylesheet" href="styles.css"> <!-- Your CSS file -->
</head>
<body>
    <div class="container">
        <h1>Edit Student - <?php echo htmlspecialchars($student_name); ?></h1>
        <form action="edit_process.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo $student_id; ?>">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($student_name); ?>" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address"><?php echo htmlspecialchars($address); ?></textarea>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select name="class_id" required>
                    <option value="">Select Class</option>
                    <?php while ($row = $classes_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['class_id']; ?>" <?php if($row['class_id'] == $class_id) echo "selected"; ?>><?php echo htmlspecialchars($row['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Current Image</label><br>
                <img src="uploads/<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($student_name); ?>" class="thumbnail"><br>
                <label>New Image (optional)</label>
                <input type="file" name="image" accept="image/jpeg, image/png">
            </div>
            <div class="form-group">
                <button type="submit" name="submit">Save Changes</button>
                <a href="view.php?id=<?php echo $student_id; ?>" class="btn cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
<?php
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $student_id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $class_id = $_POST['class_id'];
    
    // Check if a new image file is uploaded
    if(!empty($_FILES["image"]["name"])) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES["image"]["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check !== false) {
            if(in_array($fileType, array("jpg", "jpeg", "png"))) {
                // Upload file to server
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $targetFilePath)) {
                    // Get current image filename to delete later
                    $sql = "SELECT image FROM student WHERE id = ?";
                    if($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("i", $student_id);
                        $stmt->execute();
                        $stmt->bind_result($current_image);
                        $stmt->fetch();
                        $stmt->close();
                        
                        // Update student details including new image
                        $sql = "UPDATE student SET name = ?, email = ?, address = ?, class_id = ?, image = ? WHERE id = ?";
                        if($stmt = $conn->prepare($sql)) {
                            $stmt->bind_param("sssisi", $name, $email, $address, $class_id, $fileName, $student_id);
                            if($stmt->execute()) {
                                // Delete old image file from server
                                unlink("uploads/" . $current_image);
                                header("location: view.php?id=" . $student_id);
                                exit();
                            } else {
                                echo "Error updating student.";
                            }
                        }
                        $stmt->close();
                    }
                } else {
                    echo "Error uploading image.";
                }
            } else {
                echo "Only JPG, JPEG, PNG files are allowed.";
            }
        } else {
            echo "File is not an image.";
        }
    } else {
        // Update student details without changing the image
        $sql = "UPDATE student SET name = ?, email = ?, address = ?, class_id = ? WHERE id = ?";
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssii", $name, $email, $address, $class_id, $student_id);
            if($stmt->execute()) {
                header("location: view.php?id=" . $student_id);
                exit();
            } else {
                echo "Error updating student.";
            }
        }
        $stmt->close();
    }
} else {
    header("location: index.php");
    exit();
}

$conn->close();
?>
<?php
require_once "config.php";

if(isset($_GET['id'])) {
    $student_id = $_GET['id'];
    
    // Fetch current image filename to delete from server
    $sql = "SELECT image FROM student WHERE id = ?";
    if($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->bind_result($image);
        $stmt->fetch();
        $stmt->close();
        
        // Delete student from database
        $sql = "DELETE FROM student WHERE id = ?";
        if($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $student_id);
            if($stmt->execute()) {
                // Delete image file from server
                unlink("uploads/" . $image);
                header("location: index.php");
                exit();
            } else {
                echo "Error deleting student.";
            }
        }
        $stmt->close();
    } else {
        echo "Error fetching data.";
        exit();
    }
} else {
    echo "Invalid request.";
    exit();
}

$conn->close();
?>
<?php
require_once "config.php";

// Fetch all classes
$sql = "SELECT * FROM classes";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Classes - School Demo</title>
    <link rel="stylesheet" href="styles.css"> <!-- Your CSS file -->
</head>
<body>
    <div class="container">
        <h1>Manage Classes</h1>
        <a href="add_class.php" class="btn">Add New Class</a>
        <table>
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                    <td>
                        <a href="edit_class.php?id=<?php echo $row['class_id']; ?>">Edit</a> |
                        <a href="delete_class.php?id=<?php echo $row['class_id']; ?>" onclick="return confirm('Are you sure you want to delete this class?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>


