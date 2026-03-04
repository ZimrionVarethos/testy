// BookingRequest.java
package com.beningrental.app.model.request;

import com.google.gson.annotations.SerializedName;

public class BookingRequest {
    @SerializedName("vehicle_id")      private String vehicleId;
    @SerializedName("start_date")      private String startDate;
    @SerializedName("end_date")        private String endDate;
    @SerializedName("pickup_address")  private String pickupAddress;
    @SerializedName("pickup_lat")      private Double pickupLat;
    @SerializedName("pickup_lng")      private Double pickupLng;
    @SerializedName("dropoff_address") private String dropoffAddress;
    @SerializedName("notes")           private String notes;

    public BookingRequest(String vehicleId, String startDate, String endDate,
                          String pickupAddress, String notes) {
        this.vehicleId     = vehicleId;
        this.startDate     = startDate;
        this.endDate       = endDate;
        this.pickupAddress = pickupAddress;
        this.notes         = notes;
    }

    // Builder pattern untuk kemudahan
    public BookingRequest setPickupCoords(double lat, double lng) {
        this.pickupLat = lat;
        this.pickupLng = lng;
        return this;
    }

    public BookingRequest setDropoffAddress(String address) {
        this.dropoffAddress = address;
        return this;
    }
}
