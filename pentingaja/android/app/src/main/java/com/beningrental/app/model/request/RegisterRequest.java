// RegisterRequest.java
package com.beningrental.app.model.request;

import com.google.gson.annotations.SerializedName;

public class RegisterRequest {
    @SerializedName("name")                  private String name;
    @SerializedName("email")                 private String email;
    @SerializedName("phone")                 private String phone;
    @SerializedName("password")              private String password;
    @SerializedName("password_confirmation") private String passwordConfirmation;

    public RegisterRequest(String name, String email, String phone,
                           String password, String passwordConfirmation) {
        this.name                 = name;
        this.email                = email;
        this.phone                = phone;
        this.password             = password;
        this.passwordConfirmation = passwordConfirmation;
    }
}
