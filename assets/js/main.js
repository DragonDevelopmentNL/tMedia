document.addEventListener('DOMContentLoaded', function() {
    // Handle likes
    const likeButtons = document.querySelectorAll('.like-btn');
    likeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            fetch('api/like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    const likeCount = this.textContent.split(' ')[1];
                    this.textContent = `❤️ ${parseInt(likeCount) + 1}`;
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Handle shares
    const shareButtons = document.querySelectorAll('.share-btn');
    shareButtons.forEach(button => {
        button.addEventListener('click', function() {
            const postId = this.dataset.postId;
            const postUrl = `${window.location.origin}/post.php?id=${postId}`;
            
            if (navigator.share) {
                navigator.share({
                    title: 'TBerichten Post',
                    text: 'Bekijk dit bericht op TBerichten!',
                    url: postUrl
                })
                .catch(error => console.error('Error sharing:', error));
            } else {
                // Fallback for browsers that don't support Web Share API
                const tempInput = document.createElement('input');
                tempInput.value = postUrl;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                
                alert('Link gekopieerd naar klembord!');
            }
        });
    });

    // Handle profile image upload
    const profileImageInput = document.getElementById('profile_image');
    if(profileImageInput) {
        profileImageInput.addEventListener('change', function() {
            if(this.files && this.files[0]) {
                const form = this.closest('form');
                form.submit();
            }
        });
    }
}); 