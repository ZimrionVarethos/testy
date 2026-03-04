// BookingListResponse.java
package com.beningrental.app.model.response;

import com.beningrental.app.model.Booking;
import com.google.gson.annotations.SerializedName;
import java.util.List;

public class BookingListResponse {
    @SerializedName("success") private boolean success;
    @SerializedName("message") private String message;
    @SerializedName("data")    private List<Booking> data;
    @SerializedName("meta")    private VehicleListResponse.Meta meta;

    public boolean       isSuccess() { return success; }
    public String        getMessage(){ return message; }
    public List<Booking> getData()   { return data; }
    public VehicleListResponse.Meta getMeta() { return meta; }
}
