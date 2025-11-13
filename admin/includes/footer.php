    </main>

    <!-- Notification -->
    <div id="notification" class="notification"></div>

    <script>
    // Notification system
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        notification.textContent = message;
        notification.className = `notification ${type}`;
        notification.classList.add('show');

        setTimeout(() => {
            notification.classList.remove('show');
        }, 3000);
    }

    // Auto-hide alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        });

        // File upload drag and drop
        const uploadAreas = document.querySelectorAll('.file-upload-area');
        uploadAreas.forEach(area => {
            area.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            area.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            area.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const fileInput = this.parentElement.querySelector('input[type="file"]');
                    if (fileInput) {
                        fileInput.files = files;
                        if (fileInput.onchange) {
                            fileInput.onchange();
                        }
                    }
                }
            });
        });

        // Image preview functionality
        window.previewImage = function(input, previewId) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById(previewId);
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        };

        // Password toggle functionality
        document.querySelectorAll('.password-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const type = input.type === 'password' ? 'text' : 'password';
                input.type = type;
                this.className = type === 'password' ? 'fas fa-eye password-toggle' : 'fas fa-eye-slash password-toggle';
            });
        });
    });

    // Color picker functionality
    document.addEventListener('DOMContentLoaded', function() {
        const colorInputs = document.querySelectorAll('input[type="color"]');
        colorInputs.forEach(input => {
            input.addEventListener('change', function() {
                const preview = this.parentElement.querySelector('.color-preview');
                if (preview) {
                    preview.style.backgroundColor = this.value;
                }
            });
        });
    });
    </script>
</body>
</html>