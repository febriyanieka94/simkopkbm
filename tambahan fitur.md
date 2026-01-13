# Additional Implementation Plan: Student Data Improvements

This document outlines the detailed requirements for upcoming features. It is structured to provide clear instructions for AI agents regarding database changes, UI/UX expectations, and business logic.

---

## 🚀 Core Objectives

1.  **Implement Periodic Data**: Track physical growth (Weight, Height, Head Circumference) for early childhood and elementary students.
2.  **Enhance Student Profiles**: Add detailed biographical and administrative status fields.
3.  **Visual Excellence**: Use **Flux UI** and **Tailwind CSS 4** to ensure a premium, modern interface that matches the existing application aesthetic.

---

## 📊 1. Student Periodic Data (Data Periodik)

### **Context**

Physical growth monitoring is mandatory for PAUD (Early Childhood) and Paket A (Elementary) students. Data is recorded per semester.

### **Database Schema: `student_periodic_records`**

| Column               | Type         | Description                         |
| :------------------- | :----------- | :---------------------------------- |
| `id`                 | BigInt (PK)  |                                     |
| `student_profile_id` | Foreign Key  | References `student_profiles.id`    |
| `academic_year_id`   | Foreign Key  | References `academic_years.id`      |
| `semester`           | Enum/TinyInt | `1` (Ganjil), `2` (Genap)           |
| `weight`             | Float        | BB (kg)                             |
| `height`             | Float        | TB (cm)                             |
| `head_circumference` | Float        | Lingkar Kepala (cm)                 |
| `recorded_by`        | Foreign Key  | User ID (the teacher who filled it) |

### **UI/UX Requirements**

-   **Placement**: Add a "Periodic Analysis" or "Growth Tracking" section in the Student Detail page (`resources/views/livewire/pages/students/show.blade.php`).
-   **Input Method**: Use a **Flux Modal** or **Slide-over** for adding/editing records.
-   **Display**: A clean table or timeline chart showing growth over semesters.
-   **Integration**: This data must be accessible via the Student Report (Raport) generation logic.

### **Business Logic**

-   **Constraint**: The form should only be visible/active if the student's level is 'PAUD' or 'Paket A'.
-   **Authorization**: Only the assigned Class Teacher or Admin can record this data.

---

## 👤 2. Enhanced Student Profiles

### **Database Migration: `student_profiles` Updates**

Add the following columns to the existing `student_profiles` table:

| Column            | Type    | Description                                       |
| :---------------- | :------ | :------------------------------------------------ |
| `birth_order`     | Integer | Anak ke-                                          |
| `total_siblings`  | Integer | Dari ... bersaudara                               |
| `previous_school` | String  | Asal Sekolah (Nullable for PAUD/Paket A entry)    |
| `status`          | Enum    | `baru`, `mutasi`, `naik_kelas`, `lulus`, `keluar` |

### **UI Changes**

-   **Forms**: Update `resources/views/livewire/pages/students/create.blade.php` and `edit.blade.php`.
-   **Components**: Use `<flux:select>` for status and `<flux:input type="number">` for birth order details.
-   **Validation**:
    -   `previous_school` is required for Paket B and Paket C.
    -   `status` defaults to `baru` on creation.

---

## 🧪 3. Verification & Testing

### **Pest Feature Tests**

-   `tests/Feature/StudentPeriodicDataTest.php`:
    -   Ensure only PAUD/Paket A students can have periodic data.
    -   Verify semester-based uniqueness (one record per student per semester per academic year).
-   `tests/Feature/StudentProfileUpdateTest.php`:
    -   Verify new fields are saved correctly.
    -   Verify validation logic for `previous_school`.

---

## 🎨 4. Design Guidelines (Reminder)

-   **Colors**: Use the application's primary palette (refer to `index.css`).
-   **Interactivity**: Use `wire:loading` for form submissions.
-   **Feedback**: Use `<flux:toast>` or similar for success/error messages.
-   **Dark Mode**: Ensure all new components are fully compatible with dark mode.
