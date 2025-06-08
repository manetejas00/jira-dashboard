<template>
    <div class="container">
        <div class="text-center" style="margin: 20px 0px 20px 0px">
            <a href="https://shouts.dev/" target="_blank"></a><br />
            <span class="text-secondary">Jira Dashboard</span>
        </div>

        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="collapse navbar-collapse">
                <!-- for logged-in user-->
                <div class="navbar-nav" v-if="isLoggedIn">
                    <router-link to="/dashboard" class="nav-item nav-link"
                        >Dashboard</router-link
                    >
                    <router-link to="/books" class="nav-item nav-link"
                        >Jira Tasks</router-link
                    >
                    <a
                        class="nav-item nav-link"
                        style="cursor: pointer"
                        @click="logout"
                        >Logout</a
                    >
                </div>
                <!-- for non-logged user-->
                <div class="navbar-nav" v-else>
                    <router-link to="/" class="nav-item nav-link"
                        >Home</router-link
                    >
                    <router-link to="/login" class="nav-item nav-link"
                        >login</router-link
                    >
                    <router-link to="/register" class="nav-item nav-link"
                        >Register</router-link
                    >
                </div>
            </div>
        </nav>
        <br />
        <router-view />
    </div>
</template>

<script>
export default {
    name: "App",
    data() {
        return {
            isLoggedIn: false,
        };
    },
    created() {
        if (window.Laravel.isLoggedin) {
            this.isLoggedIn = true;
        }
    },
    methods: {
        logout(e) {
            e.preventDefault();
            this.$axios
                .post("/api/logout")
                .then((response) => {
                    if (response.data.success) {
                        window.location.href = "/";
                    } else {
                        console.error("Logout failed:", response.data.message);
                    }
                })
                .catch((error) => {
                    console.error("Logout error:", error.response);
                });
        },
    },
};
</script>
