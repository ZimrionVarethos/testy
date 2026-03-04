// BookingResponse.java
package com.beningrental.app.model.response;

import com.beningrental.app.model.Booking;
import com.google.gson.annotations.SerializedName;

public class BookingResponse {
    @SerializedName("success") private boolean success;
    @SerializedName("message") private String message;
    @SerializedName("data")    private Booking data;

    public boolean isSuccess() { return success; }
    public String  getMessage(){ return message; }
    public Booking getData()   { return data; }
}
