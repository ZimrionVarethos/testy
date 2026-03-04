package com.beningrental.app.ui.booking;

import android.app.DatePickerDialog;
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
import com.beningrental.app.model.request.BookingRequest;
import com.beningrental.app.model.response.BookingResponse;
import com.beningrental.app.utils.SessionManager;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

/**
 * Activity untuk membuat booking baru.
 * Menerima intent extras:
 *   - vehicle_id   : String
 *   - vehicle_name : String
 *   - price_per_day: long
 */
public class CreateBookingActivity extends AppCompatActivity {

    private EditText  etPickupAddress, etNotes;
    private TextView  tvStartDate, tvEndDate, tvTotal, tvVehicleName;
    private Button    btnStartDate, btnEndDate, btnBook;
    private ProgressBar progressBar;

    private ApiService     apiService;
    private SessionManager sessionManager;

    private String vehicleId;
    private long   pricePerDay;
    private String startDate = null;
    private String endDate   = null;

    private final SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd", Locale.getDefault());

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_booking);

        apiService     = RetrofitClient.getApiService(this);
        sessionManager = new SessionManager(this);

        vehicleId    = getIntent().getStringExtra("vehicle_id");
        pricePerDay  = getIntent().getLongExtra("price_per_day", 0);
        String vehicleName = getIntent().getStringExtra("vehicle_name");

        initViews();
        tvVehicleName.setText(vehicleName);
        setupListeners();
    }

    private void initViews() {
        etPickupAddress = findViewById(R.id.et_pickup_address);
        etNotes         = findViewById(R.id.et_notes);
        tvStartDate     = findViewById(R.id.tv_start_date);
        tvEndDate       = findViewById(R.id.tv_end_date);
        tvTotal         = findViewById(R.id.tv_total);
        tvVehicleName   = findViewById(R.id.tv_vehicle_name);
        btnStartDate    = findViewById(R.id.btn_start_date);
        btnEndDate      = findViewById(R.id.btn_end_date);
        btnBook         = findViewById(R.id.btn_book);
        progressBar     = findViewById(R.id.progress_bar);
    }

    private void setupListeners() {
        btnStartDate.setOnClickListener(v -> showDatePicker(true));
        btnEndDate.setOnClickListener(v   -> showDatePicker(false));
        btnBook.setOnClickListener(v      -> submitBooking());
    }

    private void showDatePicker(boolean isStart) {
        Calendar cal = Calendar.getInstance();
        new DatePickerDialog(this, (view, year, month, day) -> {
            Calendar selected = Calendar.getInstance();
            selected.set(year, month, day);
            String dateStr = sdf.format(selected.getTime());

            if (isStart) {
                startDate = dateStr;
                tvStartDate.setText(dateStr);
            } else {
                endDate = dateStr;
                tvEndDate.setText(dateStr);
            }
            calculateTotal();
        },
        cal.get(Calendar.YEAR),
        cal.get(Calendar.MONTH),
        cal.get(Calendar.DAY_OF_MONTH)
        ).show();
    }

    private void calculateTotal() {
        if (startDate == null || endDate == null) return;
        try {
            long diff = sdf.parse(endDate).getTime() - sdf.parse(startDate).getTime();
            int days  = Math.max(1, (int) (diff / (1000 * 60 * 60 * 24)));
            long total = days * pricePerDay;
            tvTotal.setText(String.format("Total: Rp %,.0f (%d hari)", (double) total, days)
                .replace(",", "."));
        } catch (Exception e) {
            tvTotal.setText("Total: -");
        }
    }

    private void submitBooking() {
        String pickup = etPickupAddress.getText().toString().trim();
        String notes  = etNotes.getText().toString().trim();

        if (startDate == null || endDate == null) {
            Toast.makeText(this, "Pilih tanggal mulai dan selesai.", Toast.LENGTH_SHORT).show();
            return;
        }
        if (pickup.isEmpty()) {
            Toast.makeText(this, "Alamat penjemputan wajib diisi.", Toast.LENGTH_SHORT).show();
            return;
        }

        setLoading(true);

        BookingRequest request = new BookingRequest(
            vehicleId, startDate, endDate, pickup, notes.isEmpty() ? null : notes
        );

        apiService.createBooking(sessionManager.getBearerToken(), request)
            .enqueue(new Callback<BookingResponse>() {
                @Override
                public void onResponse(Call<BookingResponse> call, Response<BookingResponse> r) {
                    setLoading(false);

                    if (r.isSuccessful() && r.body() != null && r.body().isSuccess()) {
                        Toast.makeText(CreateBookingActivity.this,
                            "Booking berhasil! Kode: " + r.body().getData().getBookingCode(),
                            Toast.LENGTH_LONG).show();
                        finish();
                    } else if (r.code() == 422) {
                        Toast.makeText(CreateBookingActivity.this,
                            "Validasi gagal. Periksa kembali data Anda.", Toast.LENGTH_SHORT).show();
                    } else {
                        Toast.makeText(CreateBookingActivity.this,
                            r.body() != null ? r.body().getMessage() : "Gagal membuat booking.",
                            Toast.LENGTH_SHORT).show();
                    }
                }

                @Override
                public void onFailure(Call<BookingResponse> call, Throwable t) {
                    setLoading(false);
                    Toast.makeText(CreateBookingActivity.this,
                        "Tidak dapat terhubung ke server.", Toast.LENGTH_SHORT).show();
                }
            });
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        btnBook.setEnabled(!loading);
    }
}
