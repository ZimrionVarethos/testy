// DashboardResponse.java
package com.beningrental.app.model.response;

import com.google.gson.annotations.SerializedName;
import java.util.Map;

public class DashboardResponse {
    @SerializedName("success") private boolean success;
    @SerializedName("data")    private DashboardData data;

    public boolean       isSuccess() { return success; }
    public DashboardData getData()   { return data; }

    public static class DashboardData {
        @SerializedName("stats")         private Map<String, Object> stats;
        @SerializedName("vehicle_stats") private Map<String, Object> vehicleStats;

        public Map<String, Object> getStats()        { return stats; }
        public Map<String, Object> getVehicleStats() { return vehicleStats; }
    }
}
