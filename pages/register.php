<div class="auth-page">
    <!-- Left Panel -->
    <div class="auth-panel-left">
        <div class="auth-left-content">
            <div class="auth-brand">
                <div class="auth-brand-icon"><i class="ri-building-4-line"></i></div>
                UniHub
            </div>

            <div class="auth-illustration">🚀</div>

            <h2>Join UniHub Today!</h2>

            <p>
                Create your free account and start your journey through university clubs,
                events, and connections.
            </p>

            <div class="auth-features">
                <div class="auth-feature-item">
                    <i class="ri-checkbox-circle-line"></i> Join any club with one click
                </div>
                <div class="auth-feature-item">
                    <i class="ri-checkbox-circle-line"></i> Register for events instantly
                </div>
                <div class="auth-feature-item">
                    <i class="ri-checkbox-circle-line"></i> Get personalized notifications
                </div>
                <div class="auth-feature-item">
                    <i class="ri-checkbox-circle-line"></i> Build your university portfolio
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel -->
    <div class="auth-panel-right">
        <div class="auth-form-wrapper">
            <div class="auth-form-header">
                <h1>Create Account</h1>
                <p>
                    Already have an account?
                    <a href="/pages/login.php">Sign in here</a>
                </p>
            </div>

            <form id="registerForm" novalidate enctype="multipart/form-data">
                <input type="hidden" name="action" value="register" />
                <meta name="csrf-token" content="" />

                <!-- Profile Picture -->
                <div class="form-group">
                    <label class="form-label">
                        Profile Picture <span class="text-muted">(optional)</span>
                    </label>

                    <div class="avatar-upload-wrapper">
                        <img
                            id="avatarPreview"
                            src="/assets/images/default-avatar.png"
                            alt="Preview"
                            class="avatar-preview"
                        />

                        <div class="avatar-upload-btn">
                            <label for="profilePicInput" class="btn btn-ghost btn-sm" style="cursor:pointer">
                                <i class="ri-upload-2-line"></i> Upload Photo
                            </label>

                            <span class="text-xs text-muted">JPG, PNG up to 5MB</span>

                            <input
                                type="file"
                                id="profilePicInput"
                                name="profile_picture"
                                accept="image/*"
                                style="display:none"
                            />
                        </div>
                    </div>
                </div>

                <div class="register-grid">
                    <!-- Full Name -->
                    <div class="form-group register-grid-full">
                        <label class="form-label" for="full_name">
                            Full Name <span class="required">*</span>
                        </label>

                        <div class="form-control-icon">
                            <i class="ri-user-line input-icon"></i>
                            <input
                                type="text"
                                id="full_name"
                                name="full_name"
                                class="form-control"
                                placeholder="Your full name"
                                required
                            />
                        </div>

                        <span class="form-error"></span>
                    </div>

                    <!-- Student ID -->
                    <div class="form-group">
                        <label class="form-label" for="student_id">
                            Student ID <span class="required">*</span>
                        </label>

                        <div class="form-control-icon">
                            <i class="ri-id-card-line input-icon"></i>
                            <input
                                type="text"
                                id="student_id"
                                name="student_id"
                                class="form-control"
                                placeholder="e.g. CSE2101"
                                required
                            />
                        </div>

                        <span class="form-error"></span>
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>

                        <div class="form-control-icon">
                            <i class="ri-phone-line input-icon"></i>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                class="form-control"
                                placeholder="01XXXXXXXXX"
                            />
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="form-group register-grid-full">
                        <label class="form-label" for="email">
                            Email Address <span class="required">*</span>
                        </label>

                        <div class="form-control-icon">
                            <i class="ri-mail-line input-icon"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control"
                                placeholder="you@university.edu"
                                required
                                autocomplete="email"
                            />
                        </div>

                        <span class="form-error"></span>
                    </div>

                    <!-- Department -->
                    <div class="form-group">
                        <label class="form-label" for="department_id">
                            Department <span class="required">*</span>
                        </label>

                        <div class="form-control-icon">
                            <i class="ri-book-2-line input-icon"></i>
                            <select id="department_id" name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                <option value="1">Computer Science and Engineering</option>
                                <option value="2">Business Administration</option>
                                <option value="3">English</option>
                            </select>
                        </div>

                        <span class="form-error"></span>
                    </div>

                    <!-- Batch -->
                    <div class="form-group">
                        <label class="form-label" for="batch">
                            Batch Year <span class="required">*</span>
                        </label>

                        <div class="form-control-icon">
                            <i class="ri-calendar-line input-icon"></i>
                            <select id="batch" name="batch" class="form-control" required>
                                <option value="">Select Batch</option>
                                <option value="2026">2026</option>
                                <option value="2025">2025</option>
                                <option value="2024">2024</option>
                                <option value="2023">2023</option>
                                <option value="2022">2022</option>
                                <option value="2021">2021</option>
                                <option value="2020">2020</option>
                                <option value="2019">2019</option>
                                <option value="2018">2018</option>
                            </select>
                        </div>

                        <span class="form-error"></span>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label class="form-label" for="password">
                            Password <span class="required">*</span>
                        </label>

                        <div class="form-control-icon">
                            <i class="ri-lock-line input-icon"></i>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control has-right-icon"
                                placeholder="Min 6 characters"
                                required
                            />

                            <span class="input-icon-right" data-toggle-password="password">
                                <i class="ri-eye-line"></i>
                            </span>
                        </div>

                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill" style="width:0"></div>
                            </div>
                            <span class="strength-text" id="strengthText"></span>
                        </div>

                        <span class="form-error"></span>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">
                            Confirm Password <span class="required">*</span>
                        </label>

                        <div class="form-control-icon">
                            <i class="ri-lock-password-line input-icon"></i>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control has-right-icon"
                                placeholder="Repeat password"
                                required
                            />

                            <span class="input-icon-right" data-toggle-password="confirm_password">
                                <i class="ri-eye-line"></i>
                            </span>
                        </div>

                        <span class="form-error"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" required />
                        <span class="form-check-label">
                            I agree to the
                            <a href="#">Terms of Service</a>
                            and
                            <a href="#">Privacy Policy</a>
                        </span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg mt-4" id="registerBtn">
                    <span class="btn-text">
                        <i class="ri-user-add-line"></i> Create Account
                    </span>

                    <span class="btn-loading">
                        <i class="ri-loader-4-line spin"></i> Creating account...
                    </span>
                </button>
            </form>
        </div>
    </div>
</div>