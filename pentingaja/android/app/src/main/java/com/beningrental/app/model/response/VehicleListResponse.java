// ============================================================
// VehicleListResponse.java
// ============================================================
package com.beningrental.app.model.response;

import com.beningrental.app.model.Vehicle;
import com.google.gson.annotations.SerializedName;
import java.util.List;

public class VehicleListResponse {
    @SerializedName("success") private boolean success;
    @SerializedName("message") private String message;
    @SerializedName("data")    private List<Vehicle> data;
    @SerializedName("meta")    private Meta meta;

    public boolean       isSuccess() { return success; }
    public String        getMessage(){ return message; }
    public List<Vehicle> getData()   { return data; }
    public Meta          getMeta()   { return meta; }

    public static class Meta {
        @SerializedName("current_page") private int currentPage;
        @SerializedName("last_page")    private int lastPage;
        @SerializedName("per_page")     private int perPage;
        @SerializedName("total")        private int total;

        public int getCurrentPage() { return currentPage; }
        public int getLastPage()    { return lastPage; }
        public int getPerPage()     { return perPage; }
        public int getTotal()       { return total; }
    }
}
