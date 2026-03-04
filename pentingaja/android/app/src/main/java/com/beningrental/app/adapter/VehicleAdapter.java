package com.beningrental.app.adapter;

import android.graphics.Color;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;

import com.beningrental.app.R;
import com.beningrental.app.model.Vehicle;
import com.bumptech.glide.Glide;

import java.util.List;

public class VehicleAdapter extends RecyclerView.Adapter<VehicleAdapter.VehicleViewHolder> {

    public interface OnItemClickListener {
        void onItemClick(Vehicle vehicle);
    }

    private final List<Vehicle>      vehicles;
    private final OnItemClickListener listener;

    public VehicleAdapter(List<Vehicle> vehicles, OnItemClickListener listener) {
        this.vehicles = vehicles;
        this.listener = listener;
    }

    @NonNull
    @Override
    public VehicleViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
            .inflate(R.layout.item_vehicle, parent, false);
        return new VehicleViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull VehicleViewHolder holder, int position) {
        holder.bind(vehicles.get(position));
    }

    @Override
    public int getItemCount() { return vehicles.size(); }

    class VehicleViewHolder extends RecyclerView.ViewHolder {

        private final ImageView ivPhoto;
        private final TextView  tvName, tvType, tvCapacity, tvPrice, tvStatus, tvRating;

        VehicleViewHolder(@NonNull View itemView) {
            super(itemView);
            ivPhoto     = itemView.findViewById(R.id.iv_vehicle_photo);
            tvName      = itemView.findViewById(R.id.tv_vehicle_name);
            tvType      = itemView.findViewById(R.id.tv_vehicle_type);
            tvCapacity  = itemView.findViewById(R.id.tv_capacity);
            tvPrice     = itemView.findViewById(R.id.tv_price);
            tvStatus    = itemView.findViewById(R.id.tv_status);
            tvRating    = itemView.findViewById(R.id.tv_rating);
        }

        void bind(Vehicle vehicle) {
            tvName.setText(vehicle.getName());
            tvType.setText(vehicle.getBrand() + " · " + vehicle.getType());
            tvCapacity.setText(vehicle.getCapacity() + " orang");
            tvPrice.setText(vehicle.getFormattedPrice() + "/hari");
            tvRating.setText("⭐ " + String.format("%.1f", vehicle.getRatingAvg()));

            // Status badge
            tvStatus.setText(getStatusLabel(vehicle.getStatus()));
            tvStatus.setBackgroundColor(getStatusColor(vehicle.getStatus()));

            // Foto dengan Glide
            String imageUrl = vehicle.getFirstImage();
            if (imageUrl != null) {
                Glide.with(itemView.getContext())
                    .load(imageUrl)
                    .placeholder(R.drawable.ic_car_placeholder)
                    .error(R.drawable.ic_car_placeholder)
                    .centerCrop()
                    .into(ivPhoto);
            } else {
                ivPhoto.setImageResource(R.drawable.ic_car_placeholder);
            }

            itemView.setOnClickListener(v -> {
                if (listener != null) listener.onItemClick(vehicle);
            });
        }

        private String getStatusLabel(String status) {
            if (status == null) return "-";
            switch (status) {
                case "available":   return "Tersedia";
                case "rented":      return "Disewa";
                case "maintenance": return "Maintenance";
                default:            return status;
            }
        }

        private int getStatusColor(String status) {
            if (status == null) return Color.GRAY;
            switch (status) {
                case "available":   return Color.parseColor("#D1FAE5"); // green-100
                case "rented":      return Color.parseColor("#DBEAFE"); // blue-100
                case "maintenance": return Color.parseColor("#FEF3C7"); // yellow-100
                default:            return Color.LTGRAY;
            }
        }
    }
}
