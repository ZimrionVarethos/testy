package com.beningrental.app.model;

import com.google.gson.annotations.SerializedName;
import java.util.List;

// ============================================================
// Vehicle.java
// ============================================================
public class Vehicle {

    @SerializedName("id")
    private String id;

    @SerializedName("name")
    private String name;

    @SerializedName("brand")
    private String brand;

    @SerializedName("model")
    private String model;

    @SerializedName("year")
    private int year;

    @SerializedName("plate_number")
    private String plateNumber;

    @SerializedName("type")
    private String type;

    @SerializedName("capacity")
    private int capacity;

    @SerializedName("price_per_day")
    private long pricePerDay;

    @SerializedName("status")
    private String status;

    @SerializedName("features")
    private List<String> features;

    @SerializedName("rating_avg")
    private double ratingAvg;

    @SerializedName("images")
    private List<String> images;

    @SerializedName("created_at")
    private String createdAt;

    // ── Getters ──────────────────────────────────────────────

    public String getId()           { return id; }
    public String getName()         { return name; }
    public String getBrand()        { return brand; }
    public String getModel()        { return model; }
    public int    getYear()         { return year; }
    public String getPlateNumber()  { return plateNumber; }
    public String getType()         { return type; }
    public int    getCapacity()     { return capacity; }
    public long   getPricePerDay()  { return pricePerDay; }
    public String getStatus()       { return status; }
    public List<String> getFeatures() { return features; }
    public double getRatingAvg()    { return ratingAvg; }
    public List<String> getImages() { return images; }
    public String getCreatedAt()    { return createdAt; }

    /** Kembalikan URL foto pertama atau null jika tidak ada. */
    public String getFirstImage() {
        return (images != null && !images.isEmpty()) ? images.get(0) : null;
    }

    /** Format harga ke Rupiah sederhana. */
    public String getFormattedPrice() {
        return "Rp " + String.format("%,.0f", (double) pricePerDay).replace(",", ".");
    }

    public boolean isAvailable() {
        return "available".equals(status);
    }
}
