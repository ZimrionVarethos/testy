// VehicleResponse.java
package com.beningrental.app.model.response;

import com.beningrental.app.model.Vehicle;
import com.google.gson.annotations.SerializedName;

public class VehicleResponse {
    @SerializedName("success") private boolean success;
    @SerializedName("message") private String message;
    @SerializedName("data")    private Vehicle data;

    public boolean isSuccess() { return success; }
    public String  getMessage(){ return message; }
    public Vehicle getData()   { return data; }
}
