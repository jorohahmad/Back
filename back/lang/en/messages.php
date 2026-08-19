<?php

return [
    // 🔐 Auth & Users
    'user_registered_successfully'     => 'User registered successfully.',
    'email_send_failed'                => 'Failed to send email.',
    'invalid_credentials'              => 'Invalid email or password.',
    'login_successful'                 => 'Login successful.',
    'logout_successful'                => 'Logout successful.',
    'account_created_pending_approval' => 'Account created successfully. Please wait for admin approval.',
    'verification_code_sent'           => 'Verification code sent to your email. Please check your inbox or spam folder.',
    'invalid_verification_code'        => 'Invalid verification code. Please check and try again.',
    'code_is_valid'                    => 'Code is valid.',
    'account_already_verified'         => 'Your account is already verified.',
    'account_verified_successfully'    => 'Account verified successfully. Thank you for your cooperation.',
    'email_not_registered'             => 'This email is not registered with us.',
    'password_reset_code_sent'         => 'Password reset code sent to your email.',
    'password_changed_successfully'    => 'Password changed successfully. You can now login.',
    'old_password_incorrect'           => 'Old password is incorrect.',
    'password_updated_successfully'    => 'Password updated successfully.',
    'profile_updated_successfully'     => 'Personal information updated successfully.',
    'profile_image_updated'            => 'Profile image updated successfully.',

    // ❤️ Favorites
    'added_to_favorites'     => 'Added to favorites successfully.',
    'removed_from_favorites' => 'Removed from favorites.',

    // 👨‍💻 Admin Panel
    'not_admin'             => 'Sorry, you do not have admin privileges.',
    'user_approved'         => 'User approved.',
    'admin_approved'        => 'Admin approved.',
    'user_rejected'         => 'User rejected.',
    'admin_rejected'        => 'Admin rejected.',
    'accepted_successfully' => 'Accepted successfully.',
    'rejected_successfully' => 'Rejected successfully.',

    // 🛒 Cart & Checkout
    'checkout_successful'             => 'Payment processed, contracts and commissions documented successfully!',
    'checkout_error'                  => 'An error occurred during checkout.',
    'added_to_cart_successfully'      => 'Item added to cart successfully.',
    'cart_is_empty'                   => 'Cart is currently empty.',
    'error_fetching_cart'             => 'Error occurred while fetching cart items.',
    'items_not_found_or_unauthorized' => 'Items not found or you are not authorized to delete them.',
    'items_deleted_from_cart'         => 'Items deleted from cart successfully.',
    'error_deleting_from_cart'        => 'An error occurred while deleting from cart.',
    'quantity_updated_successfully'   => 'Quantity updated successfully.',
    'error_fetching_receipts'         => 'An error occurred while fetching receipts.',

    // 📦 Products
    'product_created_successfully' => 'Product created successfully.',

    // 📊 Dashboard
    'dashboard_data_success'            => 'Dashboard data fetched successfully.',
    'dashboard_data_error'              => 'Error fetching dashboard data.',
    'error_processing_stats'            => 'Error processing comparative stats.',
    'error_calculating_supply_demand'   => 'Error calculating supply and demand ratio.',
    'error_fetching_performance'        => 'Error fetching performance summary.',
    'transactions_distribution_success' => 'Transactions distribution data fetched successfully.',
    'error_fetching_data'               => 'Error fetching data.',
    'quick_stats_success'               => 'Quick stats fetched successfully.',
    'error_fetching_stats'              => 'Error fetching stats.',
    'top_machines_success'              => 'Top machines fetched successfully for type: :type',
    'error_fetching_dashboard_data'     => 'Error fetching dashboard data.',

    // 🔄 General
    'added_successfully'   => 'Added successfully.',
    'updated_successfully' => 'Updated successfully.',
    'deleted_successfully' => 'Deleted successfully.',
    'success'              => 'Success',

    // Mail
    'welcome_subject'             => 'Welcome to the SwiftCart application',
    'verification_subject'        => 'Verification Mail',
    'accept_subject'              => 'Accept Mail',
    'admin_validate_subject'      => 'Validate Key',
    'approved_subject'            => 'Approved Mail',
    'forget_password_subject'     => 'Forget Password Mail',
    'rejected_subject'            => 'Rejected Mail',
    'reject_verification_subject' => 'Reject Verification Mail',

    //Rating
    'cannot_rate_self'  => 'You cannot rate yourself.',
    'rating_successful' => 'Rating submitted successfully.',

    'new_sale_title'   => 'New Sale! ',
    'new_sale_body'    => 'Your product has been sold. An amount of $:amount has been added to your balance.',
    'new_rental_title' => 'New Rental Request! ',
    'new_rental_body'  => 'Your instrument has been rented. An amount of $:amount has been added to your balance.',
    'account_approved_title' => 'Account Verified! ',
    'account_approved_body'  => 'Welcome! The administration has reviewed your details and your account has been successfully verified. You can now enjoy all features.',
    'account_rejected_title' => 'Account Verification Update',
    'account_rejected_body'  => 'Sorry, your account verification request has been rejected. Please review your details (like profile image or ID) and submit again.',
    'product_approved_title' => 'Product Approved!',
    'product_approved_body'  => 'Great! The administration has reviewed and approved your product. It is now live on the app.',
    'product_rejected_title' => 'Product Update',
    'product_rejected_body'  => 'Sorry, the product you uploaded was rejected and removed due to incomplete data or policy violations.',
    'rental_returned_title' => 'Rental Period Ended ',
    'rental_returned_body'  => 'Your rental period has completely ended, the invoice is closed, and the instruments have been successfully returned. We hope you enjoyed playing!',
    'new_announcement_title' => 'New Instrument Announcement! ',
    'new_announcement_body'  => 'A premium instrument has been added to the app: :product_name. Check it out now!',
    // Price Negotiation Notifications
    'new_offer_title'        => 'New Price Offer!',
    'new_offer_body'         => 'You have received a new offer of $:amount for the instrument :product_name.',
    
    'offer_accepted_title'   => 'Offer Accepted!',
    'offer_accepted_body'    => 'The owner accepted your offer for :product_name. You can now add it to your cart at the discounted price!',
    
    'offer_rejected_title'   => 'Offer Rejected',
    'offer_rejected_body'    => 'Sorry, the owner rejected your offer for :product_name. You can still add it to your cart at the original price.',
    'repricing_not_allowed'       => 'This instrument is not open for price negotiation.',
    'cannot_offer_own_product'    => 'You cannot make an offer on your own instrument.',
    'offer_already_pending'       => 'You already have a pending offer for this instrument.',
    'offer_sent_successfully'     => 'Offer sent successfully.',
    'unauthorized_action'         => 'You are not authorized to take action on this offer.',
    'offer_already_responded'     => 'You have already responded to this offer!',
    'offer_accepted_successfully' => 'Offer accepted successfully.',
    'offer_rejected_successfully' => 'Offer rejected.',

    'item_reached_title' => 'Your order is waiting! ',
    'item_reached_body'  => 'The instruments you ordered have successfully arrived at the office. Please proceed to pick them up.',

    // Admin Notifications
    'admin_new_user_title' => 'New User!',
    'admin_new_user_body' => 'User :user_name has just joined the app.',
    
    'admin_new_product_title' => 'New Instrument Pending Review',
    'admin_new_product_body' => 'User :owner_name has uploaded a new instrument (:product_name). Please review it.',
    
    'admin_new_checkout_title' => 'New Invoice Needs Shipping!',
    'admin_new_checkout_body' => 'User :buyer_name completed a new checkout. Transaction ID: :transaction_id.',
    
    'admin_verification_request_title' => 'Account Verification Request',
    'admin_verification_request_body' => 'User :user_name submitted a verification request. Please review their details.',
    
];