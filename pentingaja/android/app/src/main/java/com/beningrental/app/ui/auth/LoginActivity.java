package com.beningrental.app.ui.auth;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.beningrental.app.R;
import com.beningrental.app.api.ApiService;
import com.beningrental.app.api.RetrofitClient;
import com.beningrental.app.model.request.LoginRequest;
import com.beningrental.app.model.response.AuthResponse;
import com.beningrental.app.ui.vehicle.VehicleListActivity;
import com.beningrental.app.utils.SessionManager;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class LoginActivity extends AppCompatActivity {

    private EditText   etEmail, etPassword;
    private Button     btnLogin;
    private ProgressBar progressBar;
    private TextView   tvRegister;

    private ApiService     apiService;
    private SessionManager sessionManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        apiService     = RetrofitClient.getApiService(this);
        sessionManager = new SessionManager(this);

        // Jika sudah login, langsung ke home
        if (sessionManager.isLoggedIn()) {
            navigateToHome();
            return;
        }

        initViews();
        setupListeners();
    }

    private void initViews() {
        etEmail      = findViewById(R.id.et_email);
        etPassword   = findViewById(R.id.et_password);
        btnLogin     = findViewById(R.id.btn_login);
        progressBar  = findViewById(R.id.progress_bar);
        tvRegister   = findViewById(R.id.tv_register);
    }

    private void setupListeners() {
        btnLogin.setOnClickListener(v -> doLogin());

        tvRegister.setOnClickListener(v ->
            startActivity(new Intent(this, RegisterActivity.class))
        );
    }

    private void doLogin() {
        String email    = etEmail.getText().toString().trim();
        String password = etPassword.getText().toString().trim();

        // Validasi lokal
        if (email.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Email dan password tidak boleh kosong.", Toast.LENGTH_SHORT).show();
            return;
        }

        setLoading(true);

        LoginRequest request = new LoginRequest(email, password);

        apiService.login(request).enqueue(new Callback<AuthResponse>() {
            @Override
            public void onResponse(Call<AuthResponse> call, Response<AuthResponse> response) {
                setLoading(false);

                if (response.isSuccessful() && response.body() != null) {
                    AuthResponse body = response.body();

                    if (body.isSuccess()) {
                        sessionManager.saveSession(
                            body.getData().getToken(),
                            body.getData().getUser()
                        );
                        navigateToHome();
                    } else {
                        showError(body.getMessage());
                    }

                } else if (response.code() == 401) {
                    showError("Email atau password salah.");
                } else if (response.code() == 403) {
                    showError("Akun Anda telah dinonaktifkan.");
                } else {
                    showError("Terjadi kesalahan server. Coba lagi.");
                }
            }

            @Override
            public void onFailure(Call<AuthResponse> call, Throwable t) {
                setLoading(false);
                showError("Tidak dapat terhubung ke server. Periksa koneksi Anda.");
            }
        });
    }

    private void navigateToHome() {
        startActivity(new Intent(this, VehicleListActivity.class));
        finish();
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        btnLogin.setEnabled(!loading);
    }

    private void showError(String message) {
        Toast.makeText(this, message, Toast.LENGTH_LONG).show();
    }
}
