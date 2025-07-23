                            <div class="col-md-6">
                                <?php if (!empty($payment['proof_photo'])): ?>
                                <div class="text-center">
                                    <div class="proof-photo-container">
                                        <img src="../uploads/<?php echo $payment['proof_photo']; ?>" 
                                             alt="Payment Proof" 
                                             class="proof-photo"
                                             data-toggle="modal" 
                                             data-target="#proofModal<?php echo $payment['payment_number']; ?>"
                                             onerror="this.src='../assets/img/no-image.jpg'">
                                        <div class="proof-overlay">
                                            <i class="fas fa-search-plus"></i>
                                            <span>Click to view</span>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="text-center">
                                    <div class="no-proof">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                        <p class="text-muted mt-2">No proof photo available</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

<style>
        .proof-photo-container {
            position: relative;
            display: inline-block;
            margin: 10px 0;
            width: 100%;
            max-width: 300px;
            height: 300px;
            overflow: hidden;
        }
        .proof-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e3e6f0;
            padding: 5px;
            background: white;
            display: block;
        }
        .proof-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .proof-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            border-radius: 8px;
            color: white;
        }
        .proof-photo-container:hover .proof-overlay {
            opacity: 1;
        }
        .proof-overlay i {
            font-size: 2rem;
            margin-bottom: 5px;
        }
        .proof-overlay span {
            font-size: 0.9rem;
        }
        .no-proof {
            padding: 20px;
            background: #f8f9fc;
            border-radius: 8px;
            border: 1px dashed #e3e6f0;
        }
        .modal-image {
            width: 100%;
            height: auto;
            max-height: 80vh;
            object-fit: contain;
            border-radius: 8px;
        }
        .modal-content {
            background: #f8f9fc;
        }
        .modal-header {
            background: linear-gradient(45deg, #4e73df, #224abe);
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .modal-header .close {
            color: white;
            opacity: 1;
        }
        .modal-dialog {
            max-width: 800px;
        }
        .modal-body {
            padding: 20px;
            text-align: center;
        }
</style> 