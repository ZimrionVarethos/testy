package com.beningrental.app.model.response;

import com.beningrental.app.model.Booking;
import com.beningrental.app.model.User;
import com.beningrental.app.model.Vehicle;
import com.google.gson.annotations.SerializedName;
import java.util.List;
import java.util.Map;

// ============================================================
// BaseResponse.java  — Respon generik (success + message)
// ============================================================
class BaseResponse {
    @SerializedName("success") private boolean success;
    @SerializedName("message") private String message;

    public boolean isSuccess() { return success; }
    public String getMessage() { return message; }
}

// ============================================================
// Meta.java  — Pagination meta
// ============================================================
class Meta {
    @SerializedName("current_page") private int currentPage;
    @SerializedName("last_page")    private int lastPage;
    @SerializedName("per_page")     private int perPage;
    @SerializedName("total")        private int total;

    public int getCurrentPage() { return currentPage; }
    public int getLastPage()    { return lastPage; }
    public int getPerPage()     { return perPage; }
    public int getTotal()       { return total; }
}
