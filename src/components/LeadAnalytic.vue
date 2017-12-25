<template>
    <table>
        <thead>
        <tr>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="item in list">
            <th>{{item}}</th>
            <td></td>
        </tr>

        </tbody>
    </table>
</template>

<script>
    export default {
        name: 'LeadAnalytic',
        data() {
            return {
            }
        },
        created() {
            BX24.callMethod('lists.field.get', {
                IBLOCK_TYPE_ID: 'lists',
                IBLOCK_ID: '30',
                FIELD_ID: 'PROPERTY_112',
            }, (data) => {
                this.list = Object.values(data.answer.result.L.DISPLAY_VALUES_FORM);
            })
        }
    }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style scoped>
    h1, h2 {
        font-weight: normal;
    }

    ul {
        list-style-type: none;
        padding: 0;
    }

    li {
        display: inline-block;
        margin: 0 10px;
    }

    a {
        color: #42b983;
    }
</style>
