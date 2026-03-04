package com.beningrental.app.utils;

import android.content.Context;
import android.content.SharedPreferences;

import com.beningrental.app.model.User;
import com.google.gson.Gson;

/**
 * Menyimpan sesi pengguna (token, data user) di SharedPreferences.
 */
public class SessionManager {

    private static final String PREF_NAME    = "BeningRentalSession";
    private static final String KEY_TOKEN    = "auth_token";
    private static final String KEY_USER     = "auth_user";
    private static final String KEY_LOGGED   = "is_logged_in";

    private final SharedPreferences prefs;
    private final SharedPreferences.Editor editor;
    private final Gson gson = new Gson();

    public SessionManager(Context context) {
        prefs  = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
        editor = prefs.edit();
    }

    /** Simpan sesi setelah login / register berhasil. */
    public void saveSession(String token, User user) {
        editor.putBoolean(KEY_LOGGED, true);
        editor.putString(KEY_TOKEN, token);
        editor.putString(KEY_USER, gson.toJson(user));
        editor.apply();
    }

    /** Hapus sesi (logout). */
    public void clearSession() {
        editor.clear().apply();
    }

    public boolean isLoggedIn() {
        return prefs.getBoolean(KEY_LOGGED, false);
    }

    /** Ambil token untuk header Authorization: Bearer {token} */
    public String getBearerToken() {
        return "Bearer " + prefs.getString(KEY_TOKEN, "");
    }

    public String getToken() {
        return prefs.getString(KEY_TOKEN, null);
    }

    public User getUser() {
        String json = prefs.getString(KEY_USER, null);
        return json != null ? gson.fromJson(json, User.class) : null;
    }

    public void updateUser(User user) {
        editor.putString(KEY_USER, gson.toJson(user)).apply();
    }
}
