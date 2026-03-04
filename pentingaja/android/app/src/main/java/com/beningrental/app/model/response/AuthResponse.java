// ============================================================
// AuthResponse.java
// ============================================================
package com.beningrental.app.model.response;

import com.beningrental.app.model.User;
import com.google.gson.annotations.SerializedName;

public class AuthResponse {
    @SerializedName("success") private boolean success;
    @SerializedName("message") private String message;
    @SerializedName("data")    private AuthData data;

    public boolean isSuccess() { return success; }
    public String getMessage() { return message; }
    public AuthData getData()  { return data; }

    public static class AuthData {
        @SerializedName("user")  private User user;
        @SerializedName("token") private String token;

        public User   getUser()  { return user; }
        public String getToken() { return token; }
    }
}
