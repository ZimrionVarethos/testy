package com.beningrental.app.api;

import com.beningrental.app.model.request.BookingRequest;
import com.beningrental.app.model.request.LoginRequest;
import com.beningrental.app.model.request.RegisterRequest;
import com.beningrental.app.model.response.AuthResponse;
import com.beningrental.app.model.response.BookingListResponse;
import com.beningrental.app.model.response.BookingResponse;
import com.beningrental.app.model.response.DashboardResponse;
import com.beningrental.app.model.response.VehicleListResponse;
import com.beningrental.app.model.response.VehicleResponse;
import com.beningrental.app.model.response.BaseResponse;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.DELETE;
import retrofit2.http.GET;
import retrofit2.http.Header;
import retrofit2.http.POST;
import retrofit2.http.PUT;
import retrofit2.http.Path;
import retrofit2.http.Query;

/**
 * Interface Retrofit — Bening Rental API
 * Base URL: http://your-domain.com/api/v1/
 */
public interface ApiService {

    // ── Auth ─────────────────────────────────────────────────────────────

    @POST("auth/login")
    Call<AuthResponse> login(@Body LoginRequest request);

    @POST("auth/register")
    Call<AuthResponse> register(@Body RegisterRequest request);

    @POST("auth/logout")
    Call<BaseResponse> logout(@Header("Authorization") String token);

    @GET("auth/me")
    Call<AuthResponse> getProfile(@Header("Authorization") String token);

    // ── Vehicles ──────────────────────────────────────────────────────────

    @GET("vehicles")
    Call<VehicleListResponse> getVehicles(
            @Header("Authorization") String token,
            @Query("status")    String status,
            @Query("type")      String type,
            @Query("min_price") Integer minPrice,
            @Query("max_price") Integer maxPrice,
            @Query("per_page")  Integer perPage,
            @Query("page")      Integer page
    );

    @GET("vehicles/{id}")
    Call<VehicleResponse> getVehicle(
            @Header("Authorization") String token,
            @Path("id") String id
    );

    // ── Bookings ──────────────────────────────────────────────────────────

    @GET("bookings")
    Call<BookingListResponse> getBookings(
            @Header("Authorization") String token,
            @Query("status")   String status,
            @Query("per_page") Integer perPage,
            @Query("page")     Integer page
    );

    @GET("bookings/{id}")
    Call<BookingResponse> getBooking(
            @Header("Authorization") String token,
            @Path("id") String id
    );

    @POST("bookings")
    Call<BookingResponse> createBooking(
            @Header("Authorization") String token,
            @Body BookingRequest request
    );

    @POST("bookings/{id}/cancel")
    Call<BookingResponse> cancelBooking(
            @Header("Authorization") String token,
            @Path("id") String id
    );

    @POST("bookings/{id}/confirm")
    Call<BookingResponse> confirmBooking(
            @Header("Authorization") String token,
            @Path("id") String id
    );

    // ── Dashboard (Admin) ─────────────────────────────────────────────────

    @GET("dashboard")
    Call<DashboardResponse> getDashboard(@Header("Authorization") String token);

    @GET("reports")
    Call<DashboardResponse> getReports(@Header("Authorization") String token);
}
