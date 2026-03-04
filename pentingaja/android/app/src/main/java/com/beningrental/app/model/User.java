package com.beningrental.app.model;

import com.google.gson.annotations.SerializedName;

// ============================================================
// User.java
// ============================================================
public class User {

    @SerializedName("id")
    private String id;

    @SerializedName("name")
    private String name;

    @SerializedName("email")
    private String email;

    @SerializedName("phone")
    private String phone;

    @SerializedName("role")
    private String role;

    @SerializedName("is_active")
    private boolean isActive;

    @SerializedName("created_at")
    private String createdAt;

    public String getId()       { return id; }
    public String getName()     { return name; }
    public String getEmail()    { return email; }
    public String getPhone()    { return phone; }
    public String getRole()     { return role; }
    public boolean isActive()   { return isActive; }
    public String getCreatedAt(){ return createdAt; }

    public boolean isAdmin()    { return "admin".equals(role); }
    public boolean isDriver()   { return "driver".equals(role); }
    public boolean isCustomer() { return "customer".equals(role); }
}
