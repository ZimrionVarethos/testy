package com.beningrental.app.ui.vehicle;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout;

import com.beningrental.app.R;
import com.beningrental.app.adapter.VehicleAdapter;
import com.beningrental.app.api.ApiService;
import com.beningrental.app.api.RetrofitClient;
import com.beningrental.app.model.Vehicle;
import com.beningrental.app.model.response.VehicleListResponse;
import com.beningrental.app.utils.SessionManager;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class VehicleListActivity extends AppCompatActivity {

    private RecyclerView      recyclerView;
    private VehicleAdapter    adapter;
    private ProgressBar       progressBar;
    private TextView          tvEmpty;
    private SwipeRefreshLayout swipeRefresh;
    private Spinner           spinnerStatus;

    private ApiService     apiService;
    private SessionManager sessionManager;

    private int  currentPage  = 1;
    private int  lastPage     = 1;
    private boolean isLoading = false;
    private String currentStatus = null;

    private final List<Vehicle> vehicleList = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_vehicle_list);

        apiService     = RetrofitClient.getApiService(this);
        sessionManager = new SessionManager(this);

        initViews();
        setupRecyclerView();
        setupSpinner();
        setupSwipeRefresh();
        loadVehicles(true);
    }

    private void initViews() {
        recyclerView  = findViewById(R.id.recycler_view);
        progressBar   = findViewById(R.id.progress_bar);
        tvEmpty       = findViewById(R.id.tv_empty);
        swipeRefresh  = findViewById(R.id.swipe_refresh);
        spinnerStatus = findViewById(R.id.spinner_status);
    }

    private void setupRecyclerView() {
        adapter = new VehicleAdapter(vehicleList, vehicle -> {
            // Klik item → buka detail
            Intent intent = new Intent(this, VehicleDetailActivity.class);
            intent.putExtra("vehicle_id", vehicle.getId());
            startActivity(intent);
        });

        LinearLayoutManager layoutManager = new LinearLayoutManager(this);
        recyclerView.setLayoutManager(layoutManager);
        recyclerView.setAdapter(adapter);

        // Infinite scroll
        recyclerView.addOnScrollListener(new RecyclerView.OnScrollListener() {
            @Override
            public void onScrolled(RecyclerView rv, int dx, int dy) {
                if (dy <= 0 || isLoading) return;

                int visibleItemCount    = layoutManager.getChildCount();
                int totalItemCount      = layoutManager.getItemCount();
                int firstVisibleItem    = layoutManager.findFirstVisibleItemPosition();

                if ((visibleItemCount + firstVisibleItem) >= (totalItemCount - 2)
                        && currentPage < lastPage) {
                    currentPage++;
                    loadVehicles(false);
                }
            }
        });
    }

    private void setupSpinner() {
        String[] statuses = {"Semua", "Tersedia", "Disewa", "Maintenance"};
        ArrayAdapter<String> spinnerAdapter = new ArrayAdapter<>(
            this, android.R.layout.simple_spinner_item, statuses
        );
        spinnerAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        spinnerStatus.setAdapter(spinnerAdapter);

        spinnerStatus.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int pos, long id) {
                String[] apiValues = {null, "available", "rented", "maintenance"};
                currentStatus = apiValues[pos];
                loadVehicles(true);
            }
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });
    }

    private void setupSwipeRefresh() {
        swipeRefresh.setOnRefreshListener(() -> loadVehicles(true));
    }

    private void loadVehicles(boolean reset) {
        if (isLoading) return;
        isLoading = true;

        if (reset) {
            currentPage = 1;
            vehicleList.clear();
            adapter.notifyDataSetChanged();
        }

        progressBar.setVisibility(reset ? View.VISIBLE : View.GONE);

        String token = sessionManager.getBearerToken();

        apiService.getVehicles(token, currentStatus, null, null, null, 12, currentPage)
            .enqueue(new Callback<VehicleListResponse>() {
                @Override
                public void onResponse(Call<VehicleListResponse> call,
                                       Response<VehicleListResponse> response) {
                    isLoading = false;
                    progressBar.setVisibility(View.GONE);
                    swipeRefresh.setRefreshing(false);

                    if (response.isSuccessful() && response.body() != null) {
                        VehicleListResponse body = response.body();

                        if (body.isSuccess() && body.getData() != null) {
                            lastPage = body.getMeta().getLastPage();
                            int startPos = vehicleList.size();
                            vehicleList.addAll(body.getData());
                            adapter.notifyItemRangeInserted(startPos, body.getData().size());
                        }

                        tvEmpty.setVisibility(vehicleList.isEmpty() ? View.VISIBLE : View.GONE);

                    } else {
                        handleApiError(response.code());
                    }
                }

                @Override
                public void onFailure(Call<VehicleListResponse> call, Throwable t) {
                    isLoading = false;
                    progressBar.setVisibility(View.GONE);
                    swipeRefresh.setRefreshing(false);
                    Toast.makeText(VehicleListActivity.this,
                        "Gagal memuat data. Periksa koneksi.", Toast.LENGTH_SHORT).show();
                }
            });
    }

    private void handleApiError(int code) {
        if (code == 401) {
            sessionManager.clearSession();
            Toast.makeText(this, "Sesi habis. Silakan login kembali.", Toast.LENGTH_SHORT).show();
            // Redirect ke login
            finish();
        } else {
            Toast.makeText(this, "Error: " + code, Toast.LENGTH_SHORT).show();
        }
    }
}
