<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="stylesheet"
      type="text/css"
      href="{{ asset('assets/css/bootstrap.min.css') }}"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
    />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style.css') }}" />
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/responsive.css') }}" />
    <style>
      body.login-page {
        background: linear-gradient(135deg, #ff6b6b 0%, #ff8787 45%, #ffe066 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
      }

      #header.login-header {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(16px);
        border-bottom: none;
        padding: 1.5rem 0;
      }

      #header-logo .login-logo {
        max-height: 64px;
        width: auto;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
      }

      #header-logo .login-logo-text {
        letter-spacing: 4px;
        font-weight: 800;
        color: #1b1b1b;
      }

      .login-content {
        padding: 3rem 0 4rem;
        flex: 1 1 auto;
      }

      #login-container {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 24px;
        box-shadow: 0 35px 65px rgba(0, 0, 0, 0.2);
        padding: 2.5rem 2.25rem;
        position: relative;
        overflow: hidden;
      }

      #login-container::before {
        content: "";
        position: absolute;
        inset: -60% 40% auto -50%;
        height: 260px;
        background: radial-gradient(circle at center, rgba(255, 0, 0, 0.08), transparent 70%);
        z-index: 0;
      }

      #login-container > * {
        position: relative;
        z-index: 1;
      }

      .login-card-header {
        text-align: center;
        margin-bottom: 1.5rem;
      }

      .login-card-header img {
        height: 86px;
        width: auto;
        border-radius: 16px;
        box-shadow: 0 18px 35px rgba(255, 0, 0, 0.25);
      }

      .login-card-header h2 {
        margin-top: 1.25rem;
        font-weight: 800;
        letter-spacing: 1.5px;
        color: #1f1f1f;
      }

      .login-card-header p {
        margin: 0.35rem 0 0;
        color: #555;
        font-weight: 500;
        font-size: 0.95rem;
      }

      .input-group .form-control,
      .input-group .form-select {
        border-radius: 50px;
        padding: 0.75rem 1.25rem;
        border: 1px solid rgba(0, 0, 0, 0.08);
      }

      .input-group-text {
        border-radius: 50px 0 0 50px;
        background: linear-gradient(135deg, #ff6b6b, #ff8787);
        color: #fff;
        border: none;
        padding: 0.75rem 1rem;
      }

      .btn-login {
        border-radius: 50px;
        padding: 0.85rem 1.25rem;
        font-weight: 700;
        letter-spacing: 1px;
        border: none;
        background: linear-gradient(135deg, #ff6b6b, #ff8787);
        box-shadow: 0 16px 30px rgba(255, 107, 107, 0.45);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 40px rgba(255, 107, 107, 0.55);
      }

      .login-meta {
        font-size: 0.875rem;
        color: #6b6b6b;
        margin-top: 1.25rem;
        text-align: center;
      }

      footer.login-footer {
        background: transparent;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 600;
        letter-spacing: 0.5px;
      }

      @media (max-width: 576px) {
        #login-container {
          padding: 2rem 1.5rem;
        }
        .login-card-header img {
          height: 72px;
        }
      }
    </style>
    <title>Dashboard</title>
  </head>
  <body class="login-page">
    <div id="wrapper">
      <header id="header" class="navbar navbar-expand-lg login-header">
        <div class="container-fluid p-0">
          <div id="header-logo" class="seller-center-logo">
            <div
              class="d-flex justify-content-center align-items-center h-100 w-100"
            >
              <img
                src="{{ asset('assets/images/icon.png') }}"
                alt="3K Fitness"
                class="login-logo me-3"
              />
              <span class="login-logo-text d-none d-sm-inline">3K FITNESS</span>
            </div>
          </div>
        </div>
      </header>
      <div id="content" class="login-content">
        <div class="container">
          <div class="row">
            <div class="col-lg-12 d-flex justify-content-center">
              <div class="col-lg-5 col-sm-10 col-12 col-md-8 mt-5">
                <div id="login-container">
                  <div class="login-card-header">
                    <img src="{{ asset('assets/images/icon.png') }}" alt="3K Fitness" />
                    <h2>Welcome Back</h2>
                    <p>Unlock your coaching dashboard and keep classes on track.</p>
                  </div>

                  @if(session('error'))
                  <div class="alert alert-danger">{{ session('error') }}</div>
                  @endif
                  <form action="{{ route('admin.process.login') }}" method="post">
                    @csrf
                    <div class="input-group mb-3 mt-4">
                      <span class="input-group-text"
                        ><i class="fa-solid fa-user"></i
                      ></span>
                      <input
                        type="text"
                        class="form-control"
                        placeholder="Email"
                        name="email"
                        value="{{ old('email') }}"
                      />
                    </div>
                    @error('email')
                    <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <div class="input-group mb-3 mt-4">
                      <span class="input-group-text"
                        ><i class="fa-solid fa-lock"></i
                      ></span>
                      <input
                        type="password"
                        class="form-control"
                        placeholder="Password"
                        name="password"
                      />
                    </div>

                    {{-- UPDATED START: Role picker --}}
                    <div class="input-group mb-3 mt-2">
                      <span class="input-group-text">
                        <i class="fa-solid fa-user-shield"></i>
                      </span>
                      <select name="role_id" class="form-select">
                        <option value="" disabled {{ old('role_id') ? '' : 'selected' }}>Select role</option>
                        <option value="2" {{ old('role_id') == 2 ? 'selected' : '' }}>Staff</option>
                        <option value="1" {{ old('role_id') == 1 ? 'selected' : '' }}>Admin</option>
                        <option value="4" {{ old('role_id') == 4 ? 'selected' : '' }}>Super Admin</option>
                      </select>
                    </div>
                    @error('role_id')
                    <div class="alert alert-danger">{{ $message }}</div>
                    @enderror
                    {{-- UPDATED END --}}

                    @error('password') <!-- Display validation error for password -->
                    <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <button type="submit" class="btn btn-login w-100 text-uppercase">
                      Login
                    </button>
                  </form>
                  <div class="login-meta">
                    Need help? Reach out to the 3K Team for access support.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- <div style="background-color: red; width: 100px; height: 1000px"></div> -->
      <footer class="login-footer text-center py-3" style="margin-left: 0">
        Copyright. &copy; 2025 All Rights Reserved
      </footer>
    </div>
    <script
      type="text/javascript"
      src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"
    ></script>
  </body>
</html>
