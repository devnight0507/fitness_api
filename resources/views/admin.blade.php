<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fitness App - Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
        }

        .login-section {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            max-width: 500px;
            margin: 100px auto;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab {
            padding: 12px 24px;
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .tab.active {
            background: #667eea;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .file-upload {
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-upload:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .file-upload input {
            display: none;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .workout-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .workout-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .workout-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .workout-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .workout-card-body {
            padding: 20px;
        }

        .workout-card h3 {
            margin-bottom: 10px;
            color: #333;
        }

        .workout-card .meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #666;
        }

        .workout-card .actions {
            display: flex;
            gap: 10px;
        }

        .workout-card .actions button {
            flex: 1;
            padding: 8px;
            font-size: 14px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .hidden {
            display: none;
        }

        .progress {
            width: 100%;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            height: 100%;
            background: #667eea;
            transition: width 0.3s;
        }

        .logout-btn {
            float: right;
        }
    </style>
</head>
<body>
    <!-- Login Section -->
    <div id="loginSection" class="login-section">
        <h2 style="margin-bottom: 20px; color: #333;">Admin Login</h2>
        <div id="loginAlert"></div>
        <form id="loginForm">
            <div class="form-group">
                <label>Email</label>
                <input type="email" id="loginEmail" value="admin@fitness.com" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" id="loginPassword" value="password123" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
    </div>

    <!-- Main Admin Panel -->
    <div id="adminPanel" class="container hidden">
        <div class="header">
            <h1>🏋️ Fitness App Admin Panel</h1>
            <p>Manage workouts, upload videos, and assign to students</p>
            <button class="btn btn-danger logout-btn" onclick="logout()">Logout</button>
        </div>

        <div id="alert"></div>

        <div class="tabs">
            <button class="tab active" onclick="switchTab('list')">📋 Workout List</button>
            <button class="tab" onclick="switchTab('create')">➕ Create Workout</button>
        </div>

        <!-- Workout List Tab -->
        <div id="listTab" class="tab-content active">
            <div id="workoutList" class="workout-list">
                <!-- Workouts will be loaded here -->
            </div>
        </div>

        <!-- Create/Edit Workout Tab -->
        <div id="createTab" class="tab-content">
            <div class="card">
                <h2 id="formTitle" style="margin-bottom: 20px; color: #333;">Create New Workout</h2>
                <form id="workoutForm">
                    <input type="hidden" id="workoutId">

                    <div class="form-group">
                        <label>Title *</label>
                        <input type="text" id="title" required placeholder="e.g., Full Body Strength Training">
                    </div>

                    <div class="form-group">
                        <label>Category *</label>
                        <select id="category" required>
                            <option value="">Select category</option>
                            <option value="Gym">Gym</option>
                            <option value="Home">Home</option>
                            <option value="Outdoor">Outdoor</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Duration *</label>
                        <input type="text" id="duration" required placeholder="e.g., 45 min">
                    </div>

                    <div class="form-group">
                        <label>Level *</label>
                        <select id="level" required>
                            <option value="">Select level</option>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate">Intermediate</option>
                            <option value="Advanced">Advanced</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="description" placeholder="Describe the workout..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Video Upload</label>
                        <div class="file-upload" onclick="document.getElementById('videoFile').click()">
                            <input type="file" id="videoFile" accept="video/mp4,video/quicktime">
                            <p id="videoFileName">📹 Click to upload video (MP4 format)</p>
                            <small style="color: #666;">Max size: 100MB</small>
                            <div id="uploadProgress" class="progress hidden">
                                <div id="uploadProgressBar" class="progress-bar" style="width: 0%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Thumbnail URL</label>
                        <input type="url" id="thumbnail" placeholder="https://...">
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-primary">💾 Save Workout</button>
                        <button type="button" class="btn btn-secondary" onclick="resetForm()">🔄 Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // API Configuration
        const API_URL = '{{ url("/api") }}';
        let authToken = localStorage.getItem('authToken');
        let currentUser = null;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            if (authToken) {
                checkAuth();
            }

            // File input change handler
            document.getElementById('videoFile').addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    document.getElementById('videoFileName').textContent = `📹 ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                }
            });
        });

        // Check authentication
        async function checkAuth() {
            try {
                const response = await fetch(`${API_URL}/user`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    currentUser = await response.json();
                    if (currentUser.role === 'admin') {
                        showAdminPanel();
                        loadWorkouts();
                    } else {
                        showAlert('loginAlert', 'Only admins can access this panel', 'error');
                        logout();
                    }
                } else {
                    logout();
                }
            } catch (error) {
                console.error('Auth check failed:', error);
                logout();
            }
        }

        // Login
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;

            try {
                const response = await fetch(`${API_URL}/login`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (response.ok) {
                    authToken = data.token;
                    currentUser = data.user;
                    localStorage.setItem('authToken', authToken);

                    if (currentUser.role === 'admin') {
                        showAdminPanel();
                        loadWorkouts();
                    } else {
                        showAlert('loginAlert', 'Only admins can access this panel', 'error');
                    }
                } else {
                    showAlert('loginAlert', data.message || 'Login failed', 'error');
                }
            } catch (error) {
                showAlert('loginAlert', 'Connection error. Is the backend running?', 'error');
            }
        });

        // Logout
        function logout() {
            authToken = null;
            currentUser = null;
            localStorage.removeItem('authToken');
            document.getElementById('loginSection').classList.remove('hidden');
            document.getElementById('adminPanel').classList.add('hidden');
        }

        // Show admin panel
        function showAdminPanel() {
            document.getElementById('loginSection').classList.add('hidden');
            document.getElementById('adminPanel').classList.remove('hidden');
        }

        // Switch tabs
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));

            // Find and activate the correct tab button
            const tabs = document.querySelectorAll('.tab');
            if (tab === 'list') {
                tabs[0].classList.add('active');
            } else if (tab === 'create') {
                tabs[1].classList.add('active');
            }

            // Update tab content
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tab + 'Tab').classList.add('active');

            if (tab === 'list') {
                loadWorkouts();
            }
        }

        // Load workouts
        async function loadWorkouts() {
            try {
                const response = await fetch(`${API_URL}/workouts`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const workouts = await response.json();
                    displayWorkouts(workouts);
                } else {
                    showAlert('alert', 'Failed to load workouts', 'error');
                }
            } catch (error) {
                showAlert('alert', 'Connection error', 'error');
            }
        }

        // Display workouts
        function displayWorkouts(workouts) {
            const container = document.getElementById('workoutList');

            if (workouts.length === 0) {
                container.innerHTML = '<div class="card"><p style="text-align: center; color: #666;">No workouts yet. Create your first one!</p></div>';
                return;
            }

            container.innerHTML = workouts.map(workout => `
                <div class="workout-card">
                    <img src="${workout.thumbnail_path || 'https://via.placeholder.com/400x200?text=No+Image'}" alt="${workout.title}">
                    <div class="workout-card-body">
                        <h3>${workout.title}</h3>
                        <div class="meta">
                            <span>⏱️ ${workout.duration}</span>
                            <span>📊 ${workout.level}</span>
                            <span>📁 ${workout.category}</span>
                        </div>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">${workout.description || 'No description'}</p>
                        ${workout.video_path ? '<p style="color: #10b981; font-size: 14px;">✅ Video uploaded</p>' : '<p style="color: #ef4444; font-size: 14px;">❌ No video</p>'}
                        <div class="actions">
                            <button class="btn btn-primary" onclick="editWorkout(${workout.id})">✏️ Edit</button>
                            <button class="btn btn-danger" onclick="deleteWorkout(${workout.id})">🗑️ Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Create/Update workout
        document.getElementById('workoutForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const workoutId = document.getElementById('workoutId').value;
            const videoFile = document.getElementById('videoFile').files[0];

            // First, create/update the workout
            const workoutData = {
                title: document.getElementById('title').value,
                category: document.getElementById('category').value,
                duration: document.getElementById('duration').value,
                level: document.getElementById('level').value,
                description: document.getElementById('description').value,
                thumbnail_path: document.getElementById('thumbnail').value,
            };

            try {
                let workoutResponse;

                if (workoutId) {
                    // Update existing workout
                    workoutResponse = await fetch(`${API_URL}/workouts/${workoutId}`, {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${authToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(workoutData)
                    });
                } else {
                    // Create new workout
                    workoutResponse = await fetch(`${API_URL}/workouts`, {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${authToken}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(workoutData)
                    });
                }

                if (workoutResponse.ok) {
                    const savedWorkout = await workoutResponse.json();
                    let successMessage = workoutId ? 'Workout updated successfully!' : 'Workout created successfully!';

                    // If video file is selected, upload it
                    if (videoFile) {
                        try {
                            await uploadVideo(savedWorkout.id, videoFile);
                            successMessage += ' Video uploaded successfully!';
                        } catch (videoError) {
                            console.error('Video upload error:', videoError);
                            successMessage += ' Warning: Video upload failed - ' + videoError.message;
                        }
                    }

                    showAlert('alert', successMessage, 'success');
                    resetForm();
                    loadWorkouts();
                    switchTab('list');
                } else {
                    const error = await workoutResponse.json();
                    console.error('Workout save error:', error);

                    // Show validation errors if available
                    let errorMessage = error.message || 'Failed to save workout';
                    if (error.errors) {
                        const validationErrors = Object.values(error.errors).flat().join(', ');
                        errorMessage += ': ' + validationErrors;
                    }

                    showAlert('alert', errorMessage, 'error');
                }
            } catch (error) {
                console.error('Connection error:', error);
                showAlert('alert', 'Connection error: ' + error.message, 'error');
            }
        });

        // Upload video
        async function uploadVideo(workoutId, videoFile) {
            const formData = new FormData();
            formData.append('video', videoFile);
            formData.append('workout_id', workoutId);

            const progressBar = document.getElementById('uploadProgress');
            const progressFill = document.getElementById('uploadProgressBar');
            progressBar.classList.remove('hidden');

            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();

                // Upload progress
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.style.width = percentComplete + '%';
                    }
                });

                // Upload complete
                xhr.addEventListener('load', () => {
                    progressBar.classList.add('hidden');
                    progressFill.style.width = '0%';

                    if (xhr.status >= 200 && xhr.status < 300) {
                        console.log('Video uploaded successfully');
                        resolve(JSON.parse(xhr.responseText));
                    } else {
                        console.error('Video upload failed:', xhr.status, xhr.responseText);
                        reject(new Error('Video upload failed: ' + xhr.statusText));
                    }
                });

                // Upload error
                xhr.addEventListener('error', () => {
                    progressBar.classList.add('hidden');
                    progressFill.style.width = '0%';
                    console.error('Video upload error');
                    reject(new Error('Video upload error'));
                });

                xhr.open('POST', `${API_URL}/videos/upload`, true);
                xhr.setRequestHeader('Authorization', `Bearer ${authToken}`);
                xhr.send(formData);
            });
        }

        // Edit workout
        async function editWorkout(id) {
            try {
                const response = await fetch(`${API_URL}/workouts/${id}`, {
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const workout = await response.json();

                    document.getElementById('workoutId').value = workout.id;
                    document.getElementById('title').value = workout.title;
                    document.getElementById('category').value = workout.category;
                    document.getElementById('duration').value = workout.duration;
                    document.getElementById('level').value = workout.level;
                    document.getElementById('description').value = workout.description || '';
                    document.getElementById('thumbnail').value = workout.thumbnail_path || '';
                    document.getElementById('formTitle').textContent = 'Edit Workout';

                    switchTab('create');
                } else {
                    showAlert('alert', 'Failed to load workout: ' + response.statusText, 'error');
                }
            } catch (error) {
                console.error('Edit workout error:', error);
                showAlert('alert', 'Failed to load workout: ' + error.message, 'error');
            }
        }

        // Delete workout
        async function deleteWorkout(id) {
            if (!confirm('Are you sure you want to delete this workout?')) return;

            try {
                const response = await fetch(`${API_URL}/workouts/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    showAlert('alert', 'Workout deleted successfully', 'success');
                    loadWorkouts();
                } else {
                    showAlert('alert', 'Failed to delete workout', 'error');
                }
            } catch (error) {
                showAlert('alert', 'Connection error', 'error');
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('workoutForm').reset();
            document.getElementById('workoutId').value = '';
            document.getElementById('formTitle').textContent = 'Create New Workout';
            document.getElementById('videoFileName').textContent = '📹 Click to upload video (MP4 format)';
        }

        // Show alert
        function showAlert(elementId, message, type) {
            const alertDiv = document.getElementById(elementId);
            alertDiv.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => alertDiv.innerHTML = '', 5000);
        }
    </script>
</body>
</html>
