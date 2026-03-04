package com.beningrental.app.model;

import com.google.gson.annotations.SerializedName;

// ============================================================
// Booking.java
// ============================================================
public class Booking {

    @SerializedName("id")
    private String id;

    @SerializedName("booking_code")
    private String bookingCode;

    @SerializedName("status")
    private String status;

    @SerializedName("start_date")
    private String startDate;

    @SerializedName("end_date")
    private String endDate;

    @SerializedName("duration_days")
    private int durationDays;

    @SerializedName("total_price")
    private long totalPrice;

    @SerializedName("notes")
    private String notes;

    @SerializedName("pickup")
    private Location pickup;

    @SerializedName("dropoff")
    private Location dropoff;

    @SerializedName("user")
    private BookingUser user;

    @SerializedName("vehicle")
    private BookingVehicle vehicle;

    @SerializedName("driver")
    private BookingDriver driver;

    @SerializedName("accepted_at")
    private String acceptedAt;

    @SerializedName("confirmed_at")
    private String confirmedAt;

    @SerializedName("cancelled_at")
    private String cancelledAt;

    @SerializedName("created_at")
    private String createdAt;

    // ── Getters ──────────────────────────────────────────────

    public String getId()           { return id; }
    public String getBookingCode()  { return bookingCode; }
    public String getStatus()       { return status; }
    public String getStartDate()    { return startDate; }
    public String getEndDate()      { return endDate; }
    public int    getDurationDays() { return durationDays; }
    public long   getTotalPrice()   { return totalPrice; }
    public String getNotes()        { return notes; }
    public Location getPickup()     { return pickup; }
    public Location getDropoff()    { return dropoff; }
    public BookingUser getUser()       { return user; }
    public BookingVehicle getVehicle() { return vehicle; }
    public BookingDriver getDriver()   { return driver; }
    public String getAcceptedAt()   { return acceptedAt; }
    public String getConfirmedAt()  { return confirmedAt; }
    public String getCancelledAt()  { return cancelledAt; }
    public String getCreatedAt()    { return createdAt; }

    public String getFormattedPrice() {
        return "Rp " + String.format("%,.0f", (double) totalPrice).replace(",", ".");
    }

    public boolean isCancellable() {
        return status != null && !status.equals("ongoing")
                && !status.equals("completed") && !status.equals("cancelled");
    }

    // ── Nested classes ────────────────────────────────────────

    public static class Location {
        @SerializedName("address") private String address;
        @SerializedName("lat")     private Double lat;
        @SerializedName("lng")     private Double lng;

        public String getAddress() { return address; }
        public Double getLat()     { return lat; }
        public Double getLng()     { return lng; }
    }

    public static class BookingUser {
        @SerializedName("name")  private String name;
        @SerializedName("email") private String email;
        @SerializedName("phone") private String phone;

        public String getName()  { return name; }
        public String getEmail() { return email; }
        public String getPhone() { return phone; }
    }

    public static class BookingVehicle {
        @SerializedName("name")          private String name;
        @SerializedName("plate_number")  private String plateNumber;
        @SerializedName("price_per_day") private long pricePerDay;

        public String getName()        { return name; }
        public String getPlateNumber() { return plateNumber; }
        public long   getPricePerDay() { return pricePerDay; }
    }

    public static class BookingDriver {
        @SerializedName("name")           private String name;
        @SerializedName("phone")          private String phone;
        @SerializedName("license_number") private String licenseNumber;

        public String getName()          { return name; }
        public String getPhone()         { return phone; }
        public String getLicenseNumber() { return licenseNumber; }
    }
}
